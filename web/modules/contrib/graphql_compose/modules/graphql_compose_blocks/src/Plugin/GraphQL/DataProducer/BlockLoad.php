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
 *     "block_plugin_id" = @ContextDefinition("string",
 *       label = @Translation("Block plugin ID")
 *     ),
 *   }
 * )
 */
class BlockLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * LayoutDefinitionLoad constructor.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    protected BlockManagerInterface $blockManager,
    protected EntityRepositoryInterface $entityRepository,
    protected AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * Resolve the layout definition.
   */
  public function resolve(string $block_plugin_id, RefinableCacheableDependencyInterface $metadata): ?BlockPluginInterface {

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    $block_instance = $this->blockManager->createInstance($block_plugin_id);
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
