<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Load a Route or Redirect based on Path.
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
class ViewsEntityResults extends DataProducerPluginBase {

  /**
   * Resolver.
   *
   * @return array
   *   Path resolution result.
   */
  public function resolve(ViewExecutable $view, RefinableCacheableDependencyInterface $metadata): array {

    $render_context = new RenderContext();

    $results = \Drupal::service('renderer')->executeInRenderContext(
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

    $metadata->addCacheContexts($cache->getCacheContexts());
    $metadata->addCacheTags($cache->getCacheTags());

    foreach ($results['rows'] ?? [] as $entity) {
      $metadata->addCacheableDependency($entity);
    }

    // @todo figure out what todo with unsupported entity types in results.
    // My initial thinking is an exception is reasonable here.
    // If the type isnt exposed and someone goes and makes a view trying
    // to expose it, well... Feels like an error to me.
    return $results['rows'];
  }

}
