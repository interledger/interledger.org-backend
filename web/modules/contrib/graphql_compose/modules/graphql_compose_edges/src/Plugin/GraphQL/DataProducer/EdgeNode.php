<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_edges\Wrappers\EdgeInterface;

/**
 * Returns the node for an edge.
 *
 * @DataProducer(
 *   id = "edge_node",
 *   name = @Translation("Edge node"),
 *   description = @Translation("Returns the node associated with an edge."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EdgeNode")
 *   ),
 *   consumes = {
 *     "edge" = @ContextDefinition("any",
 *       label = @Translation("EdgeInterface")
 *     )
 *   }
 * )
 */
class EdgeNode extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value.
   *
   * @param \Drupal\graphql_compose_edges\Wrappers\EdgeInterface $edge
   *   The edge to retrieve the node from.
   *
   * @return mixed
   *   The graph node.
   */
  public function resolve(EdgeInterface $edge) {
    return $edge->getNode();
  }

}
