<?php

namespace Drupal\graphql_compose_search_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;

/**
 * Base class for Connection Query Helpers.
 *
 * @see \Drupal\graphql_compose_search_api\ConnectionQueryHelperInterface
 */
abstract class ConnectionQueryHelperBase implements ConnectionQueryHelperInterface
{
  /**
   * The key that is used for sorting.
   */
  protected string $sortKey;
  /**
   * The filter definitions from the field arguments..
   */
  protected string $keywords;

  /**
   * The Drupal entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The GraphQL entity buffer.
   */
  protected EntityBuffer $graphqlEntityBuffer;

  /**
   * Create a new connection query helper.
   *
   * @param string $sort_key
   *   The key that is used for sorting.
   * @param string $keywords
   *   The filter definitions from the field arguments.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphql_entity_buffer
   *   The GraphQL entity buffer.
   */
  public function __construct(string $sort_key, string $keywords, EntityTypeManagerInterface $entity_type_manager, EntityBuffer $graphql_entity_buffer)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->graphqlEntityBuffer = $graphql_entity_buffer;
    $this->sortKey = $sort_key;
    $this->keywords = $keywords;
  }

  /**
   * {@inheritdoc}
   */
  public function getForwardSortDirection(): string
  {
    return 'ASC';
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseSortDirection(): string
  {
    return 'DESC';
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateSortFunction(): ?string
  {
    return NULL;
  }
}
