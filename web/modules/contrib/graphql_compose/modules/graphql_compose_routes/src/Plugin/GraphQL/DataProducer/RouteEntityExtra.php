<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\Routing\RouteEntity;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads the entity associated with the current URL.
 *
 * @DataProducer(
 *   id = "route_entity_extra",
 *   name = @Translation("Load entity, preview or revision by url"),
 *   description = @Translation("The entity belonging to the current url."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "url" = @ContextDefinition("any",
 *       label = @Translation("The URL")
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Language"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class RouteEntityExtra extends RouteEntity implements ContainerFactoryPluginInterface {

  /**
   * The temp store service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStore;

  /**
   * The entity revision buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer
   */
  protected EntityRevisionBuffer $entityRevisionBuffer;

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

    $instance->tempStore = $container->get('tempstore.private');
    $instance->entityRevisionBuffer = $container->get('graphql.buffer.entity_revision');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($url, ?string $language, FieldContext $context): ?Deferred {

    if ($url instanceof Url) {
      [, $type] = explode('.', $url->getRouteName());
      $parameters = $url->getRouteParameters();

      // Previews.
      if (array_key_exists($type . '_preview', $parameters)) {
        $id = $parameters[$type . '_preview'];
        $type = $type . '_preview';

        // We don't have a preview buffer available.
        // Just pull it direct from the store. It's a preview.
        return new Deferred(function () use ($type, $context, $id) {
          $store = $this->tempStore->get($type);

          $entity = $store->get($id)?->getFormObject()?->getEntity();
          if (!$entity) {
            return NULL;
          }

          $access = $entity->access('view', NULL, TRUE);
          $context->addCacheableDependency($access);

          if ($access->isAllowed()) {
            return $entity;
          }

          return NULL;
        });
      }

      // Revisions.
      if (array_key_exists($type . '_revision', $parameters)) {
        $id = $parameters[$type . '_revision'];
        $type = $type . '_revision';

        // Hot swap! No rules, go hard.
        $this->entityBuffer = $this->entityRevisionBuffer;
      }
    }

    // Default GraphQL behavior.
    return parent::resolve($url, $language, $context);
  }

}
