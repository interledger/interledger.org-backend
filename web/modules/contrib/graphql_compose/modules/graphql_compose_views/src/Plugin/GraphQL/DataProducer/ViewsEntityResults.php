<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load view results.
 *
 * @DataProducer(
 *   id = "views_entity_results",
 *   name = @Translation("Views entity results"),
 *   description = @Translation("Entity results for a view"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Views results")
 *   ),
 *   consumes = {
 *     "executable" = @ContextDefinition("any",
 *       label = @Translation("View executable")
 *     )
 *   }
 * )
 */
class ViewsEntityResults extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * Resolve view entity results.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View executable.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return array
   *   View rows data.
   */
  public function resolve(ViewExecutable $view, RefinableCacheableDependencyInterface $metadata): array {

    $render_context = new RenderContext();

    $results = $this->renderer->executeInRenderContext(
      $render_context,
      function () use ($view) {
        return $view->render();
      }
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    /** @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cache */
    $cache = $results['cache'] ?? [];

    if ($cache) {
      $metadata->addCacheContexts($cache->getCacheContexts());
      $metadata->addCacheTags($cache->getCacheTags());
    }

    foreach ($results['rows'] ?? [] as $entity) {
      $metadata->addCacheableDependency($entity);
    }

    // @todo figure out what to do with unsupported entity types in results.
    // My initial thinking is an exception is reasonable here.
    // If the type isn't exposed and someone goes and makes a view trying
    // to expose it, well... Feels like an error to me.
    return $results['rows'] ?? [];
  }

}
