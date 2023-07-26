<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql_compose_edges\Wrappers\Cursor;
use Drupal\graphql_compose_edges\Wrappers\Edge;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Load nodes.
 */
class EntityTypePluginQueryHelper implements ConnectionQueryHelperInterface {

  /**
   * Create a new connection query helper.
   *
   * @param string|null $sortKey
   *   The key that is used for sorting.
   * @param string|null $langcode
   *   Language code to filter with.
   * @param string $entityTypeId
   *   The entity type to Query.
   * @param string $entityBundleId
   *   The entity bundle to Query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   */
  public function __construct(
    protected ?string $sortKey,
    protected ?string $langcode,
    protected string $entityTypeId,
    protected string $entityBundleId,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $graphqlEntityBuffer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entityTypeId);

    $query = $this->entityTypeManager
      ->getStorage($this->entityTypeId)
      ->getQuery()
      ->currentRevision()
      ->accessCheck(TRUE);

    if ($entityTypeDefinition->getBundleEntityType()) {
      $query->condition($entityTypeDefinition->getKey('bundle'), $this->entityBundleId);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCursorObject(string $cursor): ?Cursor {
    $cursor_object = Cursor::fromCursorString($cursor);

    $is_valid = $cursor_object?->isValidFor($this->sortKey, $this->entityTypeId);

    return $is_valid ? $cursor_object : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoaderPromise(array $result): SyncPromise {
    if (empty($result)) {
      // In case of no results we create a callback the returns an empty array.
      $callback = static fn () => [];
    }
    else {
      // Otherwise we create a callback that uses the GraphQL entity buffer to
      // ensure the entities for this query are only loaded once. Even if the
      // results are used multiple times.
      $callback = $this->graphqlEntityBuffer->add($this->entityTypeId, array_values($result));
    }

    return new Deferred(function () use ($callback): array {

      // Execute the buffer request.
      $entities = $callback();

      // Map each entity into an Edge wrapper.
      return array_map(function (EntityInterface $entity): Edge {

        $cursor = (string) new Cursor(
          $this->entityTypeId,
          (int) $entity->id(),
          $this->langcode,
          $this->sortKey,
          $this->getSortValue($entity)
        );

        return new Edge($entity, $cursor);

      }, $entities);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField(): string {
    return $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('id') ?: 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string {
    switch ($this->sortKey) {
      case 'CREATED_AT':
        return 'created';

      case 'UPDATED_AT':
        return 'changed';

      case 'TITLE':
        return $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('label');

      case 'STICKY':
        return 'sticky';

      case 'PROMOTED':
        return 'promote';

      default:
        return $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('id');

    }
  }

  /**
   * Get the value for an entity based on the sort key for this connection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to sort.
   *
   * @return mixed
   *   The sort value.
   */
  protected function getSortValue(EntityInterface $entity): mixed {

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
  public function getLangcodeField(): ?string {
    return $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('langcode') ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(): ?string {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishedField(): ?string {
    return $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('published') ?: NULL;
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
  public function getAggregateSortFunction(): ?string {
    return NULL;
  }

}
