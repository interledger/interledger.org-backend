<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_users\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the viewer for this request.
 *
 * This could be the authenticated user or a user that a system is acting on
 * behalf of.
 *
 * @DataProducer(
 *   id = "viewer",
 *   name = @Translation("Viewer"),
 *   description = @Translation("The actor for this request if any."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Viewer")
 *   )
 * )
 */
class Viewer extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a Viewer object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $entityBuffer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity')
    );
  }

  /**
   * Returns current user.
   *
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   Field context.
   *
   * @return \GraphQL\Deferred
   *   A promise that resolves to the current user.
   */
  public function resolve(FieldContext $context): Deferred {
    // Response must be cached based on current user as a cache context,
    // otherwise a new user would became a previous user.
    $context->addCacheableDependency($this->currentUser);

    $resolver = $this->entityBuffer->add('user', $this->currentUser->id());

    return new Deferred(function () use ($resolver, $context) {
      if (!$entity = $resolver()) {
        // If there is no entity with this id, add the list cache tags so that
        // the cache entry is purged whenever a new entity of this type is
        // saved.
        $type = $this->entityTypeManager->getDefinition('user');

        /** @var \Drupal\Core\Entity\EntityTypeInterface $type */
        $tags = $type->getListCacheTags();
        $context->addCacheTags($tags);
        return NULL;
      }

      $context->addCacheableDependency($entity);
      return $entity;
    });
  }

}
