<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql_compose_edges\Wrappers\Cursor;
use Drupal\graphql_compose_edges\Wrappers\Edge;

/**
 * Load nodes.
 */
class EntityTypePluginQueryHelper implements ConnectionQueryHelperInterface {

  /**
   * Create a new connection query helper.
   *
   * @param string|null $sortKey
   *   The key that is used for sorting.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   * @param string $entityType
   *   The entity type to Query.
   * @param string $entityBundle
   *   The entity bundle to Query.
   */
  public function __construct(
    protected ?string $sortKey,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $graphqlEntityBuffer,
    protected string $entityType,
    protected string $entityBundle,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getQuery() : QueryInterface {

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entityType);

    $query = $this->entityTypeManager->getStorage($this->entityType)
      ->getQuery()
      ->currentRevision()
      ->accessCheck(TRUE);

    if ($entityTypeDefinition->getBundleEntityType()) {
      $query->condition($entityTypeDefinition->getKey('bundle'), $this->entityBundle);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor) : ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    return !is_null($cursor_object) && $cursor_object->isValidFor($this->sortKey, $this->entityType)
      ? $cursor_object
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField() : string {
    return $this->entityTypeManager->getDefinition($this->entityType)->getKey('id') ?: 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderPromise(array $result) : SyncPromise {
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
            (string) new Cursor(
              $this->entityType,
              (int) $entity->id(),
              $this->sortKey,
              $this->getSortValue($entity)
            )
          ),
          $callback()
        );
      }
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField() : string {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return 'created';

      case 'UPDATED_AT':
        return 'changed';

      case 'TITLE':
        return $this->entityTypeManager->getDefinition($this->entityType)->getKey('label');

      case 'STICKY':
        return 'sticky';

      case 'PROMOTED':
        return 'promote';

      default:
        return $this->entityTypeManager->getDefinition($this->entityType)->getKey('id');

    }
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to sory.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(EntityInterface $entity) {

    assert($entity instanceof ContentEntityInterface);

    switch ($this->sortKey) {
      case 'CREATED_AT':
        return isset($entity->created) ? (int) $entity->get('created')->value : 0;

      case 'UPDATED_AT':
        return isset($entity->changed) ? (int) $entity->get('changed')->value : 0;

      case 'TITLE':
        return $entity->label();

      case 'STICKY':
        return isset($entity->sticky) ? (int) $entity->get('sticky')->value : 0;

      case 'PROMOTED':
        return isset($entity->promote) ? (int) $entity->get('promote')->value : 0;

      default:
        return $entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForwardSortDirection(): string {
    return 'ASC';
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseSortDirection(): string {
    return 'DESC';
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateSortFunction() : ?string {
    return NULL;
  }

}
