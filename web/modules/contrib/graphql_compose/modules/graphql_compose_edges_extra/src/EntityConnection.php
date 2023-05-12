<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra;

use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql_compose_edges_extra\Wrappers\EdgeInterface;
use GraphQL\Error\UserError;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Provides a new paginated entity query.
 *
 * @package Drupal\graphql_compose\GraphQL\Query
 */
class EntityConnection implements ConnectionInterface {

  /**
   * The number of nodes a client is allowed to fetch on this connection.
   */
  protected const MAX_LIMIT = 100;

  /**
   * The query for this connection that knows how to fetch data.
   */
  protected ConnectionQueryHelperInterface $queryHelper;

  /**
   * Fetch the first N results.
   */
  protected ?int $first = NULL;

  /**
   * Fetch the last N results.
   */
  protected ?int $last = NULL;

  /**
   * The cursor that results were fetched after.
   */
  protected ?string $after = NULL;

  /**
   * The cursor that results were fetched before.
   */
  protected ?string $before = NULL;

  /**
   * Whether the sorting is requested in reversed order.
   */
  protected bool $reverse = FALSE;

  /**
   * The result-set of this connection.
   */
  protected ?SyncPromise $result;

  /**
   * Create a new PaginatedEntityQuery.
   *
   * @param \Drupal\graphql_compose\GraphQL\ConnectionQueryHelperInterface $query_helper
   *   The query helper that knows how to fetch the data for this connection.
   */
  public function __construct(ConnectionQueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function setPagination(?int $first, ?string $after, ?int $last, ?string $before, ?bool $reverse) : self {
    // Disallow changing pagination after a query has been performed because the
    // way we treat the results depends on it.
    if ($this->hasResult()) {
      throw new \RuntimeException("Cannot change pagination after a query for a connection has been executed.");
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
   * {@inheritdoc}
   */
  public function pageInfo() : SyncPromise {
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
   */
  public function edges() : SyncPromise {
    return $this->getOrderedResult();
  }

  /**
   * {@inheritdoc}
   */
  public function nodes() : SyncPromise {
    // Just loop over the edges and get the node. If this turns out not to be
    // performant enough then we'll have to change the return value for
    // QueryHelper's and apply the edges in ::getEdges.
    return $this->getOrderedResult()
      ->then(
        static fn ($edges) => array_map(
          static fn (EdgeInterface $edge) => $edge->getNode(),
          $edges
        )
      );
  }

  /**
   * Applies slicing and reordering to the result so that it can be transmitted.
   *
   * The result from the database may be out of order and have overfetched. When
   * returning edges or nodes, this needs to be compensated in the same way.
   * This function removes the overfetching and ensures the results are in the
   * requested order.
   */
  protected function getOrderedResult() : SyncPromise {
    return $this->getResult()->then(function ($edges) {
      // To allow for pagination we over-fetch results by one above the limits
      // so we must fix that now.
      $edges = array_slice($edges, 0, $this->first ?? $this->last);

      if ($this->shouldReverseResultEdges()) {
        $edges = array_reverse($edges);
      }

      return $edges;
    });
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
  protected function shouldReverseResultEdges() : bool {
    return !is_null($this->last);
  }

  /**
   * Whether this connection has a result.
   *
   * @return bool
   *   Whether this connection has a result.
   */
  protected function hasResult() : bool {
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
  protected function getResult() : SyncPromise {
    if (!$this->hasResult()) {
      $this->result = $this->execute();
    }
    return $this->result;
  }

  /**
   * Execute the query to fetch the entities in this connection.
   *
   * @return \GraphQL\Executor\Promise\Adapter\SyncPromise
   *   A promise that resolves to the edges of this connection.
   */
  protected function execute() : SyncPromise {
    $query = $this->queryHelper->getQuery();

    $filter = $this->queryHelper->getFilter();
    $sort_field = $this->queryHelper->getSortField();
    $id_field = $this->queryHelper->getIdField();

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
        throw new UserError("invalid cursor " . $cursor);
      }
      $pagination_condition = $query->orConditionGroup();

      $operator = (!is_null($this->before) && !$this->reverse) || (!is_null($this->after) && $this->reverse) ? '<' : '>';
      $cursor_value = $cursor_object->getSortValue();
      $pagination_condition->condition($sort_field, $cursor_value, $operator);
      // If the sort field is different than the ID then it's not guaranteed to
      // be unique. However, above we exclude values that are the same as those
      // of the cursor. We want to include those but use the ID to make sure
      // they're on the correct side of the cursor.
      if ($sort_field !== $id_field) {
        $pagination_condition->condition(
          $query->andConditionGroup()
            ->condition($sort_field, $cursor_value, '=')
            ->condition($id_field, $cursor_object->getBackingId(), $operator)
        );
      }

      $query->condition($pagination_condition);
    }

    // From assertValidPagination we know that we either have a first or a last.
    $limit = $this->first ?? $this->last;

    // Fetch N + 1 so we know if there are more pages.
    $query->range(0, $limit + 1);

    $aggregate_function = $this->queryHelper->getAggregateSortFunction();

    if ($query instanceof QueryAggregateInterface && $aggregate_function) {
      $query->sortAggregate(
        $sort_field,
        $aggregate_function,
        $field_query_order
      );
    }
    else {
      $query->sort(
        $sort_field,
        $field_query_order
      );

      // @todo https://www.drupal.org/project/social/issues/3191638
      //   This should also happen for aggregated sorting when Drupal core is
      //   fixed.
      // To ensure a consistent sorting for duplicate fields we add a secondary
      // sort based on the ID.
      if ($sort_field !== $id_field) {
        $query->sort(
          $id_field,
          $id_query_order
        );
      }
    }

    if ($filter) {
      $this->applyFilter($query, $filter);
    }

    if (\Drupal::moduleHandler()->moduleExists('domain')) {
      $domain = \Drupal::service('domain.negotiator')->getActiveId();
      if ($domain) {
        $query->condition('field_domain_access', $domain, 'IN');
      }
    }

    // Fetch the result for the query.
    $result = $query->execute();

    return $this->queryHelper->getLoaderPromise($result);
  }

  /**
   * Apply the specified filter conditions to the query.
   *
   * Recursively picks up all filters and aggregates them into condition groups
   * according to the nested structure of the filter argument.
   *s
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query object.
   * @param mixed $filter
   *   The filter definitions from the field arguments.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object.
   */
  protected function applyFilter(QueryInterface $query, $filter)
  {
    if (!empty($filter) && is_array($filter)) {
      // Conditions can be disabled. Check we are not adding an empty condition group.
      $filterConditions = $this->buildFilterConditions($query, $filter);
      if (count($filterConditions->conditions())) {
        $query->condition($filterConditions);
      }
    }

    return $query;
  }

  /**
   * Recursively builds the filter condition groups.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query object.
   * @param array $filter
   *   The filter definitions from the field arguments.
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   The generated condition group according to the given filter definitions.
   *
   * @throws \GraphQL\Error\Error
   *   If the given operator and value for a filter are invalid.
   */
  protected function buildFilterConditions(QueryInterface $query, array $filter)
  {
    $conjunction = !empty($filter['conjunction']) ? $filter['conjunction'] : 'AND';
    $group = $conjunction === 'AND' ? $query->andConditionGroup() : $query->orConditionGroup();

    // Apply filter conditions.
    $conditions = !empty($filter['conditions']) ? $filter['conditions'] : [];
    foreach ($conditions as $condition) {
      // Check if we need to disable this condition.
      if (isset($condition['enabled']) && empty($condition['enabled'])) {
        continue;
      }

      $field = $condition['field'];
      $value = !empty($condition['value']) ? $condition['value'] : NULL;
      $operator = !empty($condition['operator']) ? $this->getOperator($condition['operator']) : NULL;
      $language = !empty($condition['language']) ? $condition['language'] : NULL;

      // We need at least a value or an operator.
      if (empty($operator) && empty($value)) {
        throw new UserError(sprintf("Missing value and operator in filter for '%s'.", $field));
      }
      // Unary operators need a single value.
      elseif (!empty($operator) && $this->isUnaryOperator($operator)) {
        if (empty($value) || count($value) > 1) {
          throw new UserError(sprintf("Unary operators must be associated with a single value (field '%s').", $field));
        }

        // Pick the first item from the values.
        $value = reset($value);
      }
      // Range operators need exactly two values.
      elseif (!empty($operator) && $this->isRangeOperator($operator)) {
        if (empty($value) || count($value) !== 2) {
          throw new UserError(sprintf("Range operators must require exactly two values (field '%s').", $field));
        }
      }
      // Null operators can't have a value set.
      elseif (!empty($operator) && $this->isNullOperator($operator)) {
        if (!empty($value)) {
          throw new UserError(sprintf("Null operators must not be associated with a filter value (field '%s').", $field));
        }
      }

      // If no operator is set, however, we default to EQUALS or IN, depending
      // on whether the given value is an array with one or more than one items.
      if (empty($operator)) {
        $value = count($value) === 1 ? reset($value) : $value;
        $operator = is_array($value) ? 'IN' : '=';
      }

      // Add the condition for the current field.
      $group->condition($field, $value, $operator, $language);
    }

    // Apply nested filter group conditions.
    $groups = !empty($filter['groups']) ? $filter['groups'] : [];
    foreach ($groups as $args) {
      // By default, we use AND condition groups.
      // Conditions can be disabled. Check we are not adding an empty condition group.
      $filterConditions = $this->buildFilterConditions($query, $args);
      if (count($filterConditions->conditions())) {
        $group->condition($filterConditions);
      }
    }

    return $group;
  }

  /**
   * Returns valid operator based on enum
   *
   * @param string $operator
   *   The query operator to check against.
   *
   * @return string
   *   The valid operator
   */
  protected function getOperator($operator)
  {
    $operators = ["EQUAL" => "=", "NOT_EQUAL" => "<>", "SMALLER_THAN" => "<", "SMALLER_THAN_OR_EQUAL" => "<=", "GREATER_THAN" => ">", "GREATER_THAN_OR_EQUAL" => ">=", "IN" => "IN", "NOT_IN" => "NOT_IN", "LIKE" => "LIKE", "NOT_LIKE" => "NOT LIKE", "BETWEEN" => "BETWEEN", "NOT_BETWEEN" => "NOT BETWEEN", "IS_NULL" => "IS NULL", "IS_NOT_NULL" => "IS NOT NULL"];
    return $operators[$operator];
  }

  /**
   * Checks if an operator is a unary operator.
   *
   * @param string $operator
   *   The query operator to check against.
   *
   * @return bool
   *   TRUE if the given operator is unary, FALSE otherwise.
   */
  protected function isUnaryOperator($operator)
  {
    $unary = ["=", "<>", "<", "<=", ">", ">=", "LIKE", "NOT LIKE"];
    return in_array($operator, $unary);
  }

  /**
   * Checks if an operator is a null operator.
   *
   * @param string $operator
   *   The query operator to check against.
   *
   * @return bool
   *   TRUE if the given operator is a null operator, FALSE otherwise.
   */
  protected function isNullOperator($operator)
  {
    $null = ["IS NULL", "IS NOT NULL"];
    return in_array($operator, $null);
  }

  /**
   * Checks if an operator is a range operator.
   *
   * @param string $operator
   *   The query operator to check against.
   *
   * @return bool
   *   TRUE if the given operator is a range operator, FALSE otherwise.
   */
  protected function isRangeOperator($operator)
  {
    $null = ["BETWEEN", "NOT BETWEEN"];
    return in_array($operator, $null);
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
  protected function assertValidPagination(?int $first, ?string $after, ?int $last, ?string $before, int $limit) : void {
    // The below if-statements are derived to be able to implement the Relay
    // connection spec in a sane way. They ensure we only ever need to care
    // about either (first and after) or (last and before) and no other
    // combinations.
    if (is_null($first) && is_null($last)) {
      throw new UserError("You must provide one of first or last");
    }
    if (!is_null($first) && !is_null($last)) {
      throw new UserError("providing both first and last is not supported");
    }
    if (!is_null($first) && !is_null($before)) {
      throw new UserError("using first with before is not supported");
    }
    if (!is_null($last) && !is_null($after)) {
      throw new UserError("using last with after is not supported");
    }
    if ($first <= 0 && !is_null($first)) {
      throw new UserError("first must be a positive integer when provided");
    }
    if ($last <= 0 && !is_null($last)) {
      throw new UserError("last must be a positive integer when provided");
    }
    if ($first > $limit) {
      throw new UserError("first may not be larger than " . $limit);
    }
    if ($last > $limit) {
      throw new UserError("first may not be larger than " . $limit);
    }
  }

}
