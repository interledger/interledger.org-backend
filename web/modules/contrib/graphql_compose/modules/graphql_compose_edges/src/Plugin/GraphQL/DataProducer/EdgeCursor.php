<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_edges\Wrappers\EdgeInterface;

/**
 * Returns the cursor for an edge.
 *
 * @DataProducer(
 *   id = "edge_cursor",
 *   name = @Translation("Edge cursor"),
 *   description = @Translation("Returns the cursor of an edge."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Cursor")
 *   ),
 *   consumes = {
 *     "edge" = @ContextDefinition("any",
 *       label = @Translation("EdgeInterface")
 *     )
 *   }
 * )
 */
class EdgeCursor extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\graphql_compose_edges\Wrappers\EdgeInterface $edge
   *   The edge to return the cursor for.
   *
   * @return mixed
   *   The cursor for this edge.
   */
  public function resolve(EdgeInterface $edge) {
    return $edge->getCursor();
  }

}
