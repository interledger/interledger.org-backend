<?php

namespace Drupal\graphql_compose_search_api;

use Drupal\Core\Entity\EntityInterface;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql_compose_edges_extra\Wrappers\Cursor;
use Drupal\graphql_compose_edges_extra\Wrappers\Edge;
use Drupal\graphql_compose_search_api\ConnectionQueryHelperBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\QueryInterface;

/**
 * Load nodes.
 */
class SearchApiPluginQueryHelper extends ConnectionQueryHelperBase
{

  /**
   * Create a new connection query helper.
   *
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param string $keywords
   *   Keywords to search by.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   * @param string $entityType
   *   The entity type to Query.
   * @param string $entityBundle
   *   The entity bundle to Query.
   */
  public function __construct(
    string $sort_key,
    string $keywords,
    EntityTypeManagerInterface $entity_type_manager,
    EntityBuffer $graphql_entity_buffer,
    protected string $entityType,
    protected string $entityBundle = '',
  ) {
    parent::__construct($sort_key, $keywords, $entity_type_manager, $graphql_entity_buffer);
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
  public function getCursorObject(string $cursor): ?Cursor
  {
    $cursor_object = Cursor::fromCursorString($cursor);

    return !is_null($cursor_object) && $cursor_object->isValidFor($this->sortKey, $this->entityType)
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField(): string
  {
    return $this->entityTypeManager->getDefinition($this->entityType)->getKey('id') ?: 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string
  {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return 'created';

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for sorting '{$this->sortKey}'");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateSortFunction(): ?string
  {
    return NULL;
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
      $callback = static fn () => [];
    }
    // Otherwise we create a callback that uses the GraphQL entity buffer to
    // ensure the entities for this query are only loaded once. Even if the
    // results are used multiple times.
    else {
      $buffer = \Drupal::service('graphql.buffer.entity');
      $callback = $buffer->add($this->entityType, array_values($result));
    }

    return new Deferred(
      function () use ($callback) {
        return array_map(
          fn (EntityInterface $entity) => new Edge(
            $entity,
            new Cursor($this->entityType, $entity->id(), $this->sortKey, $this->getSortValue($entity))
          ),
          $callback()
        );
      }
    );
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @todo add taxonomy_term weight specifics to... plugin?
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to sory.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(EntityInterface $entity)
  {

    switch ($this->sortKey) {
      case 'CREATED_AT':
        return is_callable([$entity, 'getCreatedTime']) ? $entity->getCreatedTime() : 0;

      default:
        throw new \InvalidArgumentException("Unsupported sortKey for pagination '{$this->sortKey}'");
    }
  }
}
