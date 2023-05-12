<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "block_field",
 *   type_sdl = "BlockUnion"
 * )
 */
class BlockField extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata): ?BlockPluginInterface {
    /** @var \Drupal\block_field\BlockFieldItemInterface $item */
    $block_instance = $item->getBlock();

    if (!$block_instance) {
      return NULL;
    }

    $account = \Drupal::currentUser()->getAccount();
    $access = $block_instance->access($account, TRUE);
    $metadata->addCacheableDependency($access);

    if (!$access) {
      return NULL;
    }

    return $block_instance;
  }

}
