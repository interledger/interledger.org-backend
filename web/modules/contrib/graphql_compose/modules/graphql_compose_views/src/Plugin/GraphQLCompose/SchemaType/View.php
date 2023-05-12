<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_views\Plugin\views\row\GraphQLEntityRow;
use Drupal\graphql_compose_views\Plugin\views\row\GraphQLFieldRow;
use Drupal\views\Views;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "View",
 * )
 */
class View extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Views represent collections of curated data from the site.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The ID of the view.'),
        ],
        'view' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the view.'),
        ],
        'display' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The machine name of the display.'),
        ],
        'langcode' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The language code of the view.'),
        ],
        'label' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The human friendly label of the view.'),
        ],
        'description' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The description of the view.'),
        ],
        'pageInfo' => [
          'type' => Type::nonNull(static::type('ViewPageInfo')),
          'description' => (string) $this->t('Information about the page in the view.'),
        ],
      ],
    ]);

    $viewStorage = $this->entityTypeManager->getStorage('view');

    foreach (Views::getApplicableViews('graphql_display') as $applicable_view) {
      // Destructure view and display ids.
      [$view_id, $display_id] = $applicable_view;

      /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
      if (!$view_entity = $viewStorage->load($view_id)) {
        continue;
      }

      $view = $view_entity->getExecutable();
      $view->setDisplay($display_id);

      // The underlying entity is unsupported. Dont even bother.
      if (!$base_type = $view->getBaseEntityType()) {
        continue;
      }

      /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
      $display = $view->getDisplay();
      $row_plugin = $display->getPlugin('row');

      $base_type_plugin = $this->gqlEntityTypeManager->getPluginInstance($base_type->id());
      if (!$base_type_plugin) {
        $row_type = 'UnsupportedType';
      }
      elseif ($row_plugin instanceof GraphQLEntityRow) {
        $row_type = $base_type_plugin->getUnionTypeSdl();
      }
      elseif ($row_plugin instanceof GraphQLFieldRow) {
        $row_type = $display->getGraphQlRowName();
      }

      // Get the description for the view.
      $view_description = $view->storage->get('description') ?: $this->t('Result for view @view display @display.', [
        '@view' => $view_id,
        '@display' => $display_id,
      ]);

      // Create type for view basec on View Interface.
      $types[] = new ObjectType([
        'name' => $display->getGraphQlResultName(),
        'description' => (string) $view_description,
        'interfaces' => fn () => [static::type('View')],
        'fields' => fn() => [
          'id'          => Type::nonNull(Type::id()),
          'view'        => Type::nonNull(Type::string()),
          'display'     => Type::nonNull(Type::string()),
          'langcode'    => Type::string(),
          'label'       => Type::string(),
          'description' => Type::string(),
          'results'     => Type::listOf(Type::nonNull(static::type($row_type))),
          'pageInfo'    => Type::nonNull(static::type('ViewPageInfo')),
        ],
      ]);

      $types = [
        ...$types,
        ...ViewSorts::getSortTypes($view),
        ...ViewPager::getPagerTypes($view),
        ...ViewFilters::getFilterTypes($view),
        ...ViewContextualFilters::getFilterTypes($view),
        ...ViewFields::getFieldTypes($view),
      ];

    }

    return $types;
  }

  /**
   * Extensions.
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $viewStorage = $this->entityTypeManager->getStorage('view');

    foreach (Views::getApplicableViews('graphql_display') as $applicable_view) {
      // Destructure view and display ids.
      [$view_id, $display_id] = $applicable_view;

      /** @var \Drupal\views\ViewEntityInterface|null $view_entity */
      if (!$view_entity = $viewStorage->load($view_id)) {
        continue;
      }

      $view = $view_entity->getExecutable();
      $view->setDisplay($display_id);

      /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
      $display = $view->getDisplay();

      $args = [];

      // Get the description for the view.
      $view_description = $view->storage->get('description') ?: $this->t('Query for view @view display @display.', [
        '@view' => $view_id,
        '@display' => $display_id,
      ]);

      $args = [
        ...$args,
        ...ViewSorts::getSortArgs($view),
        ...ViewPager::getPagerArgs($view),
        ...ViewFilters::getFilterArgs($view),
        ...ViewContextualFilters::getFilterArgs($view),
      ];

      $extensions[] = new ObjectType([
        'name' => 'Query',
        'fields' => fn() => [
          $display->getGraphQlQueryName() => [
            'type' => static::type($display->getGraphQlResultName()),
            'description' => (string) $view_description,
            'args' => $args,
          ],
        ],
      ]);
    }

    return $extensions;
  }

}
