<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_search_api;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides a new paginated entity query.
 *
 * @package Drupal\graphql_compose\GraphQL\Query
 */
class SuggestionConnection
{

  /**
   * The number of nodes a client is allowed to fetch on this connection.
   */
  protected const MAX_LIMIT = 100;

  /**
   * The query for this connection that knows how to fetch data.
   */
  protected ConnectionSuggestionsHelperInterface $queryHelper;

  /**
   * The result-set of this connection.
   */
  protected ?SyncPromise $result;

  /**
   * Create a new PaginatedEntityQuery.
   *
   * @param \Drupal\graphql_compose\GraphQL\ConnectionSuggestionsHelperInterface $query_helper
   *   The query helper that knows how to fetch the data for this connection.
   */
  public function __construct(ConnectionSuggestionsHelperInterface $query_helper)
  {
    $this->queryHelper = $query_helper;
  }

  /**
   * Whether this connection has a result.
   *
   * @return bool
   *   Whether this connection has a result.
   */
  protected function hasResult(): bool
  {
    return isset($this->result);
  }

  /**
   * Get the data result for this connection.
   *
   * Multiple calls to this function return the same promise.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   The result for this connection's query.
   */
  protected function getResult(): SyncPromise
  {
    if (!$this->hasResult()) {
      $this->result = $this->execute();
    }
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function suggestions(): SyncPromise
  {
    return $this->getResult();
  }

  /**
   * Execute the query to fetch the entities in this connection.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves to the edges of this connection.
   */
  protected function execute(): SyncPromise
  {

    $query = $this->queryHelper->getQuery();
    $keywords = $this->queryHelper->getKeywords();
    // sanitize the search string, replace multiple spaces with a single space...
    $keywords = preg_replace('/\s/', ' ', $keywords);

    $query->keys($keywords);
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')->createInstance('phrase');
    $parse_mode->setConjunction('AND');
    $query->setParseMode($parse_mode);

    // Fetch N + 1 so we know if there are more pages.
    $query->range(0, 10);

     // Fetch the result for the query.
    $result = $this->applySearch($query, $keywords);

    return $this->queryHelper->getLoaderPromise($result);
  }

  protected function applySearch(QueryInterface $query, string $keywords)
  {
    $execute = $query->execute();
    $arr = [];
    foreach ($execute as $item) {
      $data = $item->getExtraData('highlighted_fields');
      foreach ($data as $matches) {
        foreach ($matches as $match) {
          $removeTags = strip_tags($match);
          $words = explode(" ", $removeTags);

          foreach ($words as $value) {
            if (preg_match("|{$keywords}|", $value)) {
              $arr[] = $value;
            }
          }
        }
      }
    }

    return array_unique($arr);
  }
}
