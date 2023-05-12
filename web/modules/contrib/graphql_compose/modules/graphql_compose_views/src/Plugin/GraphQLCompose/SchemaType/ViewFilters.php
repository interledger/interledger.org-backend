<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 */
class ViewFilters {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public static function getFilterTypes(ViewExecutable $view): array {

    $types = [];

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    // Create exposed input for view filters.
    // Create the filter input.
    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    // Re-key filters by filter identifier.
    $exposed_filters = array_reduce(
      $exposed_filters,
      fn ($carry, $current) => $carry + [
        $current['expose']['identifier'] => $current,
      ],
      []
    );

    // Map the input type.
    $filter_fields = array_map(
      function ($filter) {
        $type = Type::string();

        switch ($filter['plugin_id']) {
          case 'boolean':
            $type = Type::boolean();
            break;

          case 'numeric':
            $type = Type::int();
            break;

          // This could be exanded to handle more types.
          // Things like date ranges etc.
          default:
            $type = Type::string();
            break;
        }

        if ($filter['expose']['multiple'] ?? FALSE) {
          $type = Type::listOf($type);
        }

        if ($filter['expose']['required'] ?? FALSE) {
          $type = Type::nonNull($type);
        }

        return [
          'type' => $type,
          'description' => (string) t('@label @description', [
            '@label' => $filter['expose']['label'] ?? '',
            '@description' => $filter['expose']['description'] ?? '',
          ]),
        ];
      },
      $exposed_filters
    );

    if (!empty($filter_fields)) {
      $types[] = new InputObjectType([
        'name' => $display->getGraphQlFilterInputName(),
        'fields' => fn() => $filter_fields,
      ]);
    }

    return $types;
  }

  /**
   * Dodgey callbacks for View.php.
   */
  public static function getFilterArgs(ViewExecutable $view): array {

    /** @var \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager */
    $gqlSchemaTypeManager = \Drupal::service('graphql_compose.schema_type_manager');

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    $args = [];

    $exposed_filters = array_filter(
      $display->getOption('filters') ?: [],
      fn ($filter) => !empty($filter['exposed'])
    );

    if ($exposed_filters) {
      $args['filter'] = [
        'type' => $gqlSchemaTypeManager->get($display->getGraphQlFilterInputName()),
        'description' => (string) t('Filter the view.'),
      ];
    }

    return $args;
  }

}
