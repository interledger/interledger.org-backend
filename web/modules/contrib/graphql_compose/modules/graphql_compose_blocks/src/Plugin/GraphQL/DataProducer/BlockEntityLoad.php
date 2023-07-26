<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQL\DataProducer;

use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load drupal blocks.
 *
 * @DataProducer(
 *   id = "block_entity_load",
 *   name = @Translation("Block entity"),
 *   description = @Translation("Load a block's entity."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "block_instance" = @ContextDefinition("any",
 *       label = @Translation("Block instance")
 *     ),
 *   }
 * )
 */
class BlockEntityLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BlockEntityLoad object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Drupal entity repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityRepositoryInterface $entityRepository,
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
      $container->get('entity.repository'),
    );
  }

  /**
   * Resolve the layout definition.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block_instance
   *   The block instance.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The block entity.
   */
  public function resolve(BlockPluginInterface $block_instance, RefinableCacheableDependencyInterface $metadata): ?EntityInterface {

    $entity = NULL;

    if ($block_instance instanceof BlockContentBlock) {
      $uuid = $block_instance->getDerivativeId();

      // Don't return broken block content instances.
      if (!$entity = $this->entityRepository->loadEntityByUuid('block_content', $uuid)) {
        return NULL;
      }
    }

    if (!$entity) {
      return NULL;
    }

    $metadata->addCacheableDependency($entity);

    $access = $entity->access('view', NULL, TRUE);
    $metadata->addCacheableDependency($access);

    if (!$access) {
      return NULL;
    }

    return $entity;
  }

}
