<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "comment_author",
 *   type_sdl = "CommentAuthor"
 * )
 */
class CommentAuthorItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, ContainerFactoryPluginInterface {

  use FieldProducerTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

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

    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    /** @var \Drupal\comment\CommentInterface $entity */
    $entity = $item->getEntity();

    $result = [
      'name' => $entity->getAuthorName(),
      'email' => NULL,
      'homepage' => $entity->getHomepage(),
    ];

    if ($this->currentUser->hasPermission('view user email addresses')) {
      $result['email'] = $entity->getAuthorEmail();
    }

    $metadata->addCacheableDependency($entity);
    $metadata->addCacheableDependency($this->currentUser);

    // Note: user is loaded via CommentsSchemaExtension to avoid always loading.
    if ($this->moduleHandler->moduleExists('graphql_compose_users')) {
      $result['user'] = $entity->getOwnerId();
    }

    return $result;
  }

}
