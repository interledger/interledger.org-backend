<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Wrappers;

/**
 * Default implementation for edges.
 */
class Edge implements EdgeInterface {

  /**
   * EntityEdge constructor.
   *
   * @param mixed $node
   *   The node for this edge.
   * @param string $cursor
   *   The cursor for this edge.
   */
  public function __construct(
    protected mixed $node,
    protected string $cursor
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getCursor() : string {
    return $this->cursor;
  }

  /**
   * {@inheritdoc}
   */
  public function getNode() {
    return $this->node;
  }

}
