<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQL\DataProducer;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load drupal blocks.
 *
 * @DataProducer(
 *   id = "block_load",
 *   name = @Translation("Block loader"),
 *   description = @Translation("Load a block."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Block instance")
 *   ),
 *   consumes = {
 *     "id" = @ContextDefinition("string",
 *       label = @Translation("Block plugin ID")
 *     ),
 *   }
 * )
 */
class BlockLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BlockLoad object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   Drupal block manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Drupal entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected BlockManagerInterface $blockManager,
    protected EntityRepositoryInterface $entityRepository,
    protected AccountProxyInterface $currentUser,
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
      $container->get('plugin.manager.block'),
      $container->get('entity.repository'),
      $container->get('current_user'),
    );
  }

  /**
   * Resolve the layout definition.
   *
   * @param string $id
   *   The block plugin ID.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface|null
   *   The block plugin instance.
   */
  public function resolve(string $id, RefinableCacheableDependencyInterface $metadata): ?BlockPluginInterface {

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    $block_instance = $this->blockManager->createInstance($id);
    $metadata->addCacheableDependency($block_instance);

    $access = $block_instance->access($this->currentUser->getAccount(), TRUE);
    $metadata->addCacheableDependency($access);

    if (!$access->isAllowed()) {
      return NULL;
    }

    $plugin_definition = $block_instance->getPluginDefinition();

    // Don't return broken block plugin instances.
    if ($plugin_definition['id'] === 'broken') {
      return NULL;
    }

    if ($plugin_definition['id'] === 'block_content') {
      $uuid = $block_instance->getDerivativeId();

      // Don't return broken block content instances.
      if (!$this->entityRepository->loadEntityByUuid('block_content', $uuid)) {
        return NULL;
      }
    }

    return $block_instance;
  }

}
