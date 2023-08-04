<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQL\DataProducer;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render drupal blocks.
 *
 * @DataProducer(
 *   id = "block_render",
 *   name = @Translation("Block renderer"),
 *   description = @Translation("Render a block."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Block instance")
 *   ),
 *   consumes = {
 *     "block_instance" = @ContextDefinition("any",
 *       label = @Translation("Block instance")
 *     ),
 *   }
 * )
 */
class BlockRender extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BlockRender object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Drupal entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityRepositoryInterface $entityRepository,
    protected AccountProxyInterface $currentUser,
    protected RendererInterface $renderer,
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
      $container->get('current_user'),
      $container->get('renderer'),
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
   * @return string|null
   *   The rendered block.
   */
  public function resolve(BlockPluginInterface $block_instance, RefinableCacheableDependencyInterface $metadata): ?string {
    $metadata->addCacheableDependency($block_instance);

    $access = $block_instance->access($this->currentUser->getAccount(), TRUE);
    $metadata->addCacheableDependency($access);

    if (!$access->isAllowed()) {
      return NULL;
    }

    // Render the block within a context to catch cache.
    $render_context = new RenderContext();
    $content = $this->renderer->executeInRenderContext(
      $render_context,
      function () use ($block_instance, $access): string {
        $build = [];

        // Place the content returned by the block plugin into a 'content' child
        // element, as a way to allow the plugin to have complete control of its
        // properties and rendering (for instance, its own #theme) without
        // conflicting with the properties used above.
        $build['content'] = $block_instance->build();

        CacheableMetadata::createFromRenderArray($build)
          ->addCacheableDependency($access)
          ->addCacheableDependency($block_instance)
          ->applyTo($build);

        return (string) $this->renderer->renderRoot($build);
      }
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    return (string) $content ?: NULL;
  }

}
