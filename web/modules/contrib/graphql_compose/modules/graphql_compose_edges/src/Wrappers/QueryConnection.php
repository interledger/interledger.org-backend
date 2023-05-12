<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Wrappers;

use Drupal\Core\Entity\Query\QueryInterface;
use GraphQL\Deferred;

/**
 * Helper class that wraps entity queries.
 */
class QueryConnection {

  /**
   * QueryConnection constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Entity query interface.
   */
  public function __construct(
    protected QueryInterface $query
  ) {}

  /**
   * Returns the number entities found.
   *
   * @return int
   *   Count all elements in an array, or something in an object
   */
  public function total() {
    $query = clone $this->query;
    $query->range(NULL, NULL)->count();

    /** @var int */
    return $query->execute();
  }

  /**
   * Buffer items.
   *
   * @return array|\GraphQL\Deferred
   *   Deferred or nothing.
   */
  public function items() {
    $result = $this->query->execute();

    if (empty($result)) {
      return [];
    }

    $buffer = \Drupal::service('graphql.buffer.entity');
    $callback = $buffer->add($this->query->getEntityTypeId(), array_values($result));

    return new Deferred(function () use ($callback) {
      return $callback();
    });
  }

}
