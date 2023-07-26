<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Wrappers;

/**
 * Provides a common interface for edges that DataProducers can work with.
 */
interface EdgeInterface {

  /**
   * Return the cursor for this edge.
   *
   * @return string
   *   The cursor for this edge.
   */
  public function getCursor(): string;

  /**
   * Return the node for this edge.
   *
   * @return mixed
   *   The node for this edge.
   */
  public function getNode();

}
