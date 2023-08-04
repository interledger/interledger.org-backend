<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_compose_views\Plugin\views\display\GraphQL;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add view resolution.
 *
 * @SchemaExtension(
 *   id = "view_schema_extension",
 *   name = "GraphQL Compose Views",
 *   description = @Translation("Exposed views resolution."),
 *   schema = "graphql_compose"
 * )
 */
class ViewsSchemaExtension extends SdlSchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $viewStorage = $this->entityTypeManager->getStorage('view');

    // The parent is a ViewExecutable.
    $registry->addFieldResolver(
      'View',
      'id',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->storage->uuid();
      }),
    );

    $registry->addFieldResolver(
      'View',
      'view',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->storage->id();
      }),
    );

    $registry->addFieldResolver(
      'View',
      'display',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->current_display;
      }),
    );

    $registry->addFieldResolver(
      'View',
      'langcode',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->storage->language()->getId();
      }),
    );

    $registry->addFieldResolver(
      'View',
      'label',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->storage->label();
      }),
    );

    $registry->addFieldResolver(
      'View',
      'description',
      $builder->callback(function (ViewExecutable $executable) {
        return $executable->storage->get('description');
      }),
    );

    $registry->addFieldResolver(
      'View',
      'pageInfo',
      $builder->produce('views_page_info')
        ->map('executable', $builder->fromParent())
    );

    foreach (Views::getApplicableViews('graphql_display') as $applicable_view) {
      // Destructure view and display ids.
      [$view_id, $display_id] = $applicable_view;

      /** @var \Drupal\views\ViewEntityInterface|null $view */
      if (!$view = $viewStorage->load($view_id)) {
        continue;
      }

      $executable = $view->getExecutable();
      $executable->setDisplay($display_id);

      /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
      $display = $executable->getDisplay();

      // Results.
      $display->getGraphQlResultName();

      // The parent is a ViewExecutable.
      $registry->addFieldResolver(
        'Query',
        $display->getGraphQlQueryName(),
        $builder->produce('views_executable')
          ->map('view_id', $builder->fromValue($view_id))
          ->map('display_id', $builder->fromValue($display_id))
          ->map('page', $builder->fromArgument('page'))
          ->map('page_size', $builder->fromArgument('pageSize'))
          ->map('offset', $builder->fromArgument('offset'))
          ->map('filter', $builder->fromArgument('filter'))
          ->map('contextual_filter', $builder->fromArgument('contextualFilter'))
          ->map('sort_key', $builder->fromArgument('sortKey'))
          ->map('sort_dir', $builder->fromArgument('sortDir'))
      );

      $registry->addFieldResolver(
        $display->getGraphQlResultName(),
        'results',
        $builder->produce('views_entity_results')
          ->map('executable', $builder->fromParent())
      );
    }

    $registry->addTypeResolver(
      'ViewResultUnion',
      function ($view) {
        if ($view instanceof ViewExecutable) {
          $display = $view->getDisplay();
          $display_id = $display->display['id'];

          if (!$display instanceof GraphQL) {
            throw new UserError(sprintf('View %s:%s is not a GraphQL display.', $view->id(), $display_id));
          }

          return $display->getGraphQlResultName();
        }
        throw new UserError('Could not resolve view type.');
      }
    );
  }

}
