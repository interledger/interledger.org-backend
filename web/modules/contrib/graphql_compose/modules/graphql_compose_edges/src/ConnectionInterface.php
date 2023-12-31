<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides the interface for connections.
 */
interface ConnectionInterface {

  /**
   * Specifies the pagination parameters for this query.
   *
   * This can only be called before results have been fetched.
   *
   * @param int|null $first
   *   The limit of N first results (either first XOR last must be set).
   * @param string|null $after
   *   The cursor after which to fetch results (when using `$first`).
   * @param int|null $last
   *   The limit of N last results (either first XOR last must be set).
   * @param string|null $before
   *   The cursor before which to fetch results (when using `$last`).
   * @param bool $reverse
   *   Whether the sorting is in reversed order.
   *
   * @return $this
   *   This connection instance.
   */
  public function setPagination(?int $first, ?string $after, ?int $last, ?string $before, bool $reverse): self;

  /**
   * Get the page info from the connection.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   An promise that resolves to an array containing the fields of page info.
   */
  public function pageInfo(): SyncPromise;

  /**
   * Get the edges from the connection.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves to an array of EntityEdge instances.
   */
  public function edges(): SyncPromise;

  /**
   * Get hte nodes for this connection.
   *
   * This allows bypassing of the edges in case edge information isn't needed.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves to an array of entities.
   */
  public function nodes(): SyncPromise;

  /**
   * Set the cache context for this request.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $context
   *   The cache context for this request.
   *
   * @return \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   *   The cache context for this request.
   */
  public function setCacheContext(RefinableCacheableDependencyInterface $context): RefinableCacheableDependencyInterface;

  /**
   * Set the user account to use for access checks.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to use for access checks.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account to use for access checks.
   */
  public function setAccessAccount(AccountInterface $account): AccountInterface;

}
