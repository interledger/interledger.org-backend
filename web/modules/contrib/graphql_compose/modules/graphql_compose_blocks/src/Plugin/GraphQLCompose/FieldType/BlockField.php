<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "block_field",
 *   type_sdl = "BlockUnion"
 * )
 */
class BlockField extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, ContainerFactoryPluginInterface {

  use FieldProducerTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata): ?BlockPluginInterface {
    /** @var \Drupal\block_field\BlockFieldItemInterface $item */
    $block_instance = $item->getBlock();
    if (!$block_instance) {
      return NULL;
    }

    $account = $this->currentUser->getAccount();
    $access = $block_instance->access($account, TRUE);

    $metadata->addCacheableDependency($block_instance);
    $metadata->addCacheableDependency($access);

    if (!$access->isAllowed()) {
      return NULL;
    }

    return $block_instance;
  }

}
