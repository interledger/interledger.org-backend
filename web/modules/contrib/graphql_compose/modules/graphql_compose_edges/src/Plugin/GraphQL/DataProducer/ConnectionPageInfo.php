<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_edges\ConnectionInterface;

/**
 * Produces the page info from a connection object.
 *
 * @DataProducer(
 *   id = "connection_page_info",
 *   name = @Translation("Connection page info"),
 *   description = @Translation("Returns the page info of a connection."),
 *   produces = @ContextDefinition("page_info",
 *     label = @Translation("Page Info")
 *   ),
 *   consumes = {
 *     "connection" = @ContextDefinition("any",
 *       label = @Translation("Query Connection")
 *     )
 *   }
 * )
 */
class ConnectionPageInfo extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the request.
   *
   * @param \Drupal\graphql_compose_edges\ConnectionInterface $connection
   *   The connection to return the page info for.
   *
   * @return mixed
   *   The page info for the connection.
   */
  public function resolve(ConnectionInterface $connection) {
    return $connection->pageInfo();
  }

}
