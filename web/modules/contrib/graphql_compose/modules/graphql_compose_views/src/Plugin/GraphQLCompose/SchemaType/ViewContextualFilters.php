<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 */
class ViewContextualFilters {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public static function getFilterTypes(ViewExecutable $view): array {

    $types = [];

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();

    // Create exposed input for contexual filters.
    $contextual_filters = $display->getOption('arguments') ?: [];

    $contextual_fields = array_map(
      fn () => Type::string(),
      $contextual_filters
    );

    if (!empty($contextual_fields)) {
      $types[] = new InputObjectType([
        'name' => $display->getGraphQlContextualFilterInputName(),
        'fields' => fn() => $contextual_fields,
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

    $contextual_filters = $display->getOption('arguments') ?: [];
    if ($contextual_filters) {
      $args['contextualFilter'] = [
        'type' => $gqlSchemaTypeManager->get($display->getGraphQlContextualFilterInputName()),
        'description' => (string) t('Contextual filters for the view.'),
      ];
    }

    return $args;
  }

}
