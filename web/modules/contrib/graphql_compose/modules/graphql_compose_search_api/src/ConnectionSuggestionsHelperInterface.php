<?php

namespace Drupal\graphql_compose_search_api;

use Drupal\search_api\Query\QueryInterface;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides an interface for a connection query helper.
 *
 * A connection query helper provides an EntityConnection implementation with
 * the data that it needs to fetch data on the connection in a specific
 * configuration.
 */
interface ConnectionSuggestionsHelperInterface
{
  /**
   * Get the query that's at the root of this connection.
   *
   * This is a good place to apply any filtering that has been provided by the
   * client.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   An entity query or aggregate entity query.
   */
  public function getQuery(): QueryInterface;

  /**
   *
   * @return string
   *   The filter.
   */
  public function getKeywords(): string;

  /**
   * Asynchronously turn the entity query result into edges.
   *
   * This can be used to process the results from the entity query and load them
   * using something like the GraphQL Entity Buffer. Transformative work should
   * be moved into the promise as much as possible.
   *
   * @param array $result
   *   The result of the entity query as started in getQuery.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves into the edges for this connection.
   */
  public function getLoaderPromise(array $result): SyncPromise;
}
