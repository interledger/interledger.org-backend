<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql_compose_edges\Wrappers\EdgeInterface;
use GraphQL\Error\UserError;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides a new paginated entity query.
 */
class EntityConnection implements ConnectionInterface {

  /**
   * The number of nodes a client is allowed to fetch on this connection.
   */
  protected const MAX_LIMIT = 100;

  /**
   * Fetch the first N results.
   *
   * @var int|null
   */
  protected ?int $first = NULL;

  /**
   * Fetch the last N results.
   *
   * @var int|null
   */
  protected ?int $last = NULL;

  /**
   * The cursor that results were fetched after.
   *
   * @var string|null
   */
  protected ?string $after = NULL;

  /**
   * The cursor that results were fetched before.
   *
   * @var string|null
   */
  protected ?string $before = NULL;

  /**
   * Whether the sorting is requested in reversed order.
   *
   * @var bool
   */
  protected bool $reverse = FALSE;

  /**
   * The user to check access against.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $accessAccount;

  /**
   * The cache context for this request.
   *
   * @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   */
  protected RefinableCacheableDependencyInterface $context;

  /**
   * The result-set of this connection.
   *
   * @var \GraphQL\Executor\Promise\Adapter\SyncPromise|null
   */
  protected ?SyncPromise $result;

  /**
   * Create a new PaginatedEntityQuery.
   *
   * @param \Drupal\graphql_compose_edges\ConnectionQueryHelperInterface $queryHelper
   *   The query helper that knows how to fetch the data for this connection.
   */
  public function __construct(
    protected ConnectionQueryHelperInterface $queryHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function edges(): SyncPromise {
    return $this->getResult()
      ->then($this->getAccessFilteredEdges(...))
      ->then($this->getOrderedEdges(...));
  }

  /**
   * {@inheritdoc}
   */
  public function nodes(): SyncPromise {
    return $this->getResult()
      ->then($this->getAccessFilteredEdges(...))
      ->then($this->getOrderedEdges(...))
      ->then(function ($edges) {

        // Just loop over the edges and get the node.
        return array_map(
          fn (EdgeInterface $edge) => $edge->getNode(),
          $edges
        );
      });
  }

  /**
   * Whether this connection has a result.
   *
   * @return bool
   *   Whether this connection has a result.
   */
  protected function hasResult(): bool {
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
  protected function getResult(): SyncPromise {
    if (!$this->hasResult()) {
      $this->result = $this->execute();
    }
    return $this->result;
  }

  /**
   * Filter out access restricted content as a safeguard on entityQuery.
   *
   * This is probably against the spec, but we need SOME sort of check.
   * If an entity doesn't implement hook_query_TAG_alter then we
   * can't guarantee the access check is applied.
   *
   * @param \Drupal\graphql_compose_edges\Wrappers\EdgeInterface[] $edges
   *   Edges to check access on.
   *
   * @return \Drupal\graphql_compose_edges\Wrappers\EdgeInterface[]
   *   Filtered edge nodes.
   */
  protected function getAccessFilteredEdges(array $edges): array {
    return array_filter($edges, function (EdgeInterface $edge) {
      $entity = $edge->getNode();

      $this->context->addCacheableDependency($entity);

      if ($entity instanceof AccessibleInterface) {
        $access = $entity->access('view', $this->accessAccount, TRUE);
        $this->context->addCacheableDependency($access);

        if (!$access->isAllowed()) {
          return FALSE;
        }
      }

      return TRUE;
    });
  }

  /**
   * Apply slicing and reordering to the result so that it can be transmitted.
   *
   * The result from the database may be out of order and have overfetched.
   * When returning edges or nodes, this needs to be compensated in the
   * same way. This function removes the overfetching and ensures the
   * results are in the requested order.
   *
   * @param \Drupal\graphql_compose_edges\Wrappers\EdgeInterface[] $edges
   *   Edges to check access on.
   *
   * @return \Drupal\graphql_compose_edges\Wrappers\EdgeInterface[]
   *   Filtered edge nodes.
   */
  protected function getOrderedEdges(array $edges): array {
    // To allow for pagination we over-fetch results by one above
    // the limits so we must fix that now.
    $edges = array_slice($edges, 0, $this->first ?? $this->last);

    if ($this->shouldReverseResultEdges()) {
      $edges = array_reverse($edges);
    }

    return $edges;
  }

  /**
   * Execute the query to fetch the entities in this connection.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves to the edges of this connection.
   *
   * @throws \GraphQL\Error\UserError
   *   Invalid cursor provided.
   */
  protected function execute(): SyncPromise {
    $query = $this->queryHelper->getQuery();

    $sort_field = $this->queryHelper->getSortField();
    $id_field = $this->queryHelper->getIdField();
    $langcode = $this->queryHelper->getLangcode();

    // Because MySQL only allows us to provide positive range limits (we can't
    // select backwards) we must change the query order based on the meaning of
    // first and last. This in turn is dependant on whether we're selecting in
    // ascending (non-reversed) or descending (reversed) order.
    // The order is ascending if
    // - we want the first results in a non reversed query
    // - we want the last results in a reversed query
    // The order is descending if
    // - we want the first results in a reversed query
    // - we want the last results in a non reversed query.
    $field_query_order = (!is_null($this->first) && !$this->reverse) || (!is_null($this->last) && $this->reverse) ? $this->queryHelper->getForwardSortDirection() : $this->queryHelper->getReverseSortDirection();
    $id_query_order = (!is_null($this->first) && !$this->reverse) || (!is_null($this->last) && $this->reverse) ? 'ASC' : 'DESC';

    // If a cursor is provided then we alter the condition to select the
    // elements on the correct side of the cursor.
    $cursor = $this->after ?? $this->before;
    if (!is_null($cursor)) {

      $cursor_object = $this->queryHelper->getCursorObject($cursor);
      if (is_null($cursor_object)) {
        throw new UserError(sprintf('Invalid cursor %s', $cursor));
      }

      $operator = (!is_null($this->before) && !$this->reverse) || (!is_null($this->after) && $this->reverse) ? '<' : '>';

      $cursor_sort = $cursor_object->getSortValue();
      $cursor_id = $cursor_object->getBackingId();
      $cursor_langcode = $cursor_object->getLangcode();

      if ($cursor_langcode) {
        $langcode = $cursor_langcode;
      }

      $pagination_condition = $query->orConditionGroup();
      $pagination_condition->condition($sort_field, $cursor_sort, $operator);

      // If the sort field is different than the ID then it's not guaranteed to
      // be unique. However, above we exclude values that are the same as those
      // of the cursor. We want to include those but use the ID to make sure
      // they're on the correct side of the cursor.
      if ($sort_field !== $id_field) {
        $pagination_condition->condition(
          $query->andConditionGroup()
            ->condition($sort_field, $cursor_sort, '=')
            ->condition($id_field, $cursor_id, $operator)
        );
      }

      $query->condition($pagination_condition);
    }

    if (!is_null($langcode)) {
      $langcode_field = $this->queryHelper->getLangcodeField();

      if (!is_null($langcode_field)) {

        // Fetch the desired language content and the content that is not
        // translated or not assigned a language.
        $language_condition = $query->orConditionGroup()
          ->condition($langcode_field, $langcode)
          ->condition($langcode_field, LanguageInterface::LANGCODE_NOT_APPLICABLE)
          ->condition($langcode_field, LanguageInterface::LANGCODE_NOT_SPECIFIED);

        $query->condition($language_condition);
      }
    }

    // From assertValidPagination we know that we either have a first or a last.
    $limit = $this->first ?? $this->last;

    // Fetch N + 1 so we know if there are more pages.
    $query->range(0, $limit + 1);

    $aggregate_function = $this->queryHelper->getAggregateSortFunction();

    if ($query instanceof QueryAggregateInterface && $aggregate_function) {
      $query->sortAggregate($sort_field, $aggregate_function, $field_query_order);
    }
    else {
      $query->sort($sort_field, $field_query_order);

      // @todo https://www.drupal.org/project/social/issues/3191638
      // This should also happen for aggregated sort when Drupal core is fixed.
      // To ensure a consistent sorting for duplicate fields we add a secondary
      // sort based on the ID.
      if ($sort_field !== $id_field) {
        $query->sort($id_field, $id_query_order);
      }
    }

    // Check for unpublished access.
    $published = $this->queryHelper->getPublishedField();
    $account = $this->accessAccount;

    if ($published && $account) {
      $this->context->addCacheContexts(['user.permissions']);

      $bypass = [
        'view any unpublished content',
        'view any unpublished ' . $this->queryHelper->getEntityTypeId(),
      ];

      if (!array_filter($bypass, $account->hasPermission(...))) {
        $query->condition($published, TRUE);
      }
    }

    // Fetch the result for the query.
    $result = $query->execute();

    return $this->queryHelper->getLoaderPromise($result);
  }

  /**
   * {@inheritdoc}
   */
  public function pageInfo(): SyncPromise {
    return $this->getResult()->then(function ($edges) {
      /** @var \Drupal\graphql_compose\Wrappers\Edge[] $edges */
      // If we don't have any results then we won't have any other pages either.
      if (empty($edges)) {
        return [
          'hasNextPage' => FALSE,
          'hasPreviousPage' => FALSE,
          'startCursor' => NULL,
          'endCursor' => NULL,
        ];
      }

      // Count the number of elements that we have so we can check if we have
      // future pages.
      $count = count($edges);
      // The last item is either based on the limit or on the number of fetched
      // items if it's below the limit. Correct for 0 based indexing.
      $last_index = min($this->first ?? $this->last, $count) - 1;

      return [
        // We have a next page if the before cursor was provided (we assume
        // calling code has validated the cursor) or if N first results were
        // requested and we have more.
        'hasNextPage' => $this->before !== NULL || ($this->first !== NULL && $this->first < $count),

        // We have a previous page if the after cursor was provided (we assume
        // calling code has validated the cursor) or if N last results were
        // requested and we have more.
        'hasPreviousPage' => $this->after !== NULL || ($this->last !== NULL && $this->last < $count),

        // The start cursor is always the first cursor in the result-set..
        'startCursor' => $this->shouldReverseResultEdges() ? $edges[$last_index]->getCursor() : $edges[0]->getCursor(),

        // The end cursor is always the last cursor in the result-set..
        'endCursor' => $this->shouldReverseResultEdges() ? $edges[0]->getCursor() : $edges[$last_index]->getCursor(),
      ];
    });
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   */
  public function setPagination(?int $first, ?string $after, ?int $last, ?string $before, ?bool $reverse): self {
    // Disallow changing pagination after a query has been performed
    // because the way we treat the results depends on it.
    if ($this->hasResult()) {
      throw new \RuntimeException('Cannot change pagination after a query for a connection has been executed.');
    }

    $this->assertValidPagination($first, $after, $last, $before, self::MAX_LIMIT);
    $this->first = $first;
    $this->after = $after;
    $this->last = $last;
    $this->before = $before;
    $this->reverse = (bool) $reverse;

    return $this;
  }

  /**
   * Whether the edges from our result should be reversed.
   *
   * To compensate for the ordering needed for the range selector we must
   * sometimes flip the result. The first 3 results of a non-reverse query
   * are the same as the last 3 results of a reversed query but they are in
   * reverse order.
   * The results must be flipped if
   * - we want the last results in a reversed query
   * - we want the last results in a non reversed query.
   *
   * @return bool
   *   Whether the edges returned from `getResult()` as in reverse order.
   */
  protected function shouldReverseResultEdges(): bool {
    return !is_null($this->last);
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessAccount(AccountInterface $account): AccountInterface {
    $this->accessAccount = $account;
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheContext(RefinableCacheableDependencyInterface $context): RefinableCacheableDependencyInterface {
    $this->context =& $context;
    return $context;
  }

  /**
   * Ensures the user entered limits (first/last) are valid.
   *
   * @param int|null $first
   *   Request to retrieve first n results.
   * @param string|null $after
   *   The cursor after which to fetch results.
   * @param int|null $last
   *   Request to retrieve last n results.
   * @param string|null $before
   *   The cursor before which to fetch results.
   * @param int $limit
   *   The limit on the amount of results that may be requested.
   *
   * @throws \GraphQL\Error\UserError
   *   Error thrown when a user has specified invalid arguments.
   */
  protected function assertValidPagination(?int $first, ?string $after, ?int $last, ?string $before, int $limit): void {
    // The below if-statements are derived to be able to implement the Relay
    // connection spec in a sane way. They ensure we only ever need to care
    // about either (first and after) or (last and before) and no other
    // combinations.
    if (is_null($first) && is_null($last)) {
      throw new UserError('You must provide one of first or last.');
    }
    if (!is_null($first) && !is_null($last)) {
      throw new UserError('Providing both first and last is not supported.');
    }
    if (!is_null($first) && !is_null($before)) {
      throw new UserError('Using first with before is not supported.');
    }
    if (!is_null($last) && !is_null($after)) {
      throw new UserError('Using last with after is not supported.');
    }
    if ($first <= 0 && !is_null($first)) {
      throw new UserError('First must be a positive integer when provided.');
    }
    if ($last <= 0 && !is_null($last)) {
      throw new UserError('Last must be a positive integer when provided.');
    }
    if ($first > $limit) {
      throw new UserError(sprintf('First may not be larger than %s.', $limit));
    }
    if ($last > $limit) {
      throw new UserError(sprintf('Last may not be larger than %s.', $limit));
    }
  }

}
