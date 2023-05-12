<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 */
class ViewSorts {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public static function getSortTypes(ViewExecutable $view): array {

    $types = [];

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if (!empty($exposed_sorts)) {
      $types[] = new EnumType([
        'name' => $display->getGraphQlSortInputName(),
        'values' => $display->getGraphQlSortEnums(),
      ]);
    }

    return $types;
  }

  /**
   * Dodgey callbacks for View.php.
   */
  public static function getSortArgs(ViewExecutable $view): array {

    /** @var \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager */
    $gqlSchemaTypeManager = \Drupal::service('graphql_compose.schema_type_manager');

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    $args = [];

    // Pagination enabled at a set limit.
    $exposed_sorts = array_filter(
      $display->getOption('sorts') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_sorts) {
      $args['sortKey'] = [
        'type' => $gqlSchemaTypeManager->get($display->getGraphQlSortInputName()),
        'description' => (string) t('Sort the view.'),
      ];
    }

    if ($display->getOption('exposed_form')['options']['expose_sort_order'] ?? FALSE) {
      $args['sortDir'] = [
        'type' => $gqlSchemaTypeManager->get('SortDirection'),
        'description' => (string) t('Sort the view direction.'),
      ];
    }

    return $args;
  }

}
