<?php

namespace Drupal\graphql_compose_extra_search_api;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\QueryInterface;

/**
 * Load nodes.
 */
class SearchApiPluginSuggestionsHelper extends ConnectionSuggestionsHelperBase
{

  /**
   * Create a new connection query helper.
   *
   * @param string $keywords
   *   Keywords to search by.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   */
  public function __construct(
    string $keywords,
    EntityTypeManagerInterface $entity_type_manager,
    EntityBuffer $graphql_entity_buffer
  ) {
    parent::__construct($keywords, $entity_type_manager, $graphql_entity_buffer);
  }

  /**
   * {@inheritdoc}
   *
   * @todo add conditions per plugin. eg ->condition('uid', 0, '!=') for user.
   */
  public function getQuery(): QueryInterface
  {
    $query = Index::load('bing_content')->query();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeywords(): string
  {
    return $this->keywords;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderPromise(array $result): SyncPromise
  {
    // In case of no results we create a callback the returns an empty array.
    if (empty($result)) {
      $callback = [];
    }

    $callback = ['suggestions'=> $result];

    return new Deferred(
      function () use ($callback ) {
        return $callback;
      }
    );
  }
}
