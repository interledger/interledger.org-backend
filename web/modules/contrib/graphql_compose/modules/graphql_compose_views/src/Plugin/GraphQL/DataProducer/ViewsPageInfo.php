<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Get pager info for a view.
 *
 * @DataProducer(
 *   id = "views_page_info",
 *   name = @Translation("Views page info"),
 *   description = @Translation("Metadata info on a view"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Page info results")
 *   ),
 *   consumes = {
 *     "executable" = @ContextDefinition("any",
 *       label = @Translation("View executable")
 *     )
 *   }
 * )
 */
class ViewsPageInfo extends DataProducerPluginBase {

  /**
   * Resolve extra views pager information.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View executable.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return array
   *   Path resolution result.
   */
  public function resolve(ViewExecutable $view, RefinableCacheableDependencyInterface $metadata): array {
    return [
      'offset' => $view->getOffset(),
      'page' => $view->getCurrentPage() ?: 0,
      'pageSize' => $view->getItemsPerPage(),
      'total' => $view->total_rows ?: 0,
    ];
  }

}
