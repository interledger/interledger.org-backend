<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose_views\Plugin\views\row\GraphQLFieldRow;
use Drupal\views\ViewExecutable;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use function Symfony\Component\String\u;

/**
 * {@inheritDoc}
 */
class ViewFields {

  /**
   * {@inheritdoc}
   *
   * Add dynamic view types that use View interface.
   */
  public static function getFieldTypes(ViewExecutable $view): array {

    $types = [];

    /** @var \Drupal\graphql_compose_views\Plugin\views\display\GraphQL $display */
    $display = $view->getDisplay();
    $row_plugin = $display->getPlugin('row');

    // Build new type for fields based vew.
    if (!$row_plugin instanceof GraphQLFieldRow) {
      return [];
    }

    $options = $display->getPlugin('row')->options['field_options'];
    $fields = $view->display_handler->getOption('fields') ?: [];
    $fields = array_filter($fields, function ($field) {
      return empty($field['exclude']);
    });

    $type_fields = [];
    foreach (array_keys($fields) as $field_id) {
      $option = $options[$field_id];

      $default_alias = u($field_id)
        ->trimPrefix('field_')
        ->camel()
        ->toString();

      // Alias set by user.
      $field_alias = $option['alias'] ?: $default_alias;

      // Raw output could be anything.
      // We're going to need a custom scalar and dump junk into it.
      if ($option['type'] === 'Scalar') {
        $types[] = $custom_scalar = new CustomScalarType([
          'name' => $display->getGraphQlName($field_alias . 'Field'),
          'description' => (string) t('Output of @field. Contents unknown.', [
            '@field' => $field_alias,
          ]),
        ]);

        $field_type = $custom_scalar;
      }
      else {
        $field_type = call_user_func([Type::class, $option['type']]);
      }

      $type_fields[$field_alias] = $field_type;
    }

    $types[] = new ObjectType([
      'name' => $display->getGraphQlRowName(),
      'description' => (string) t('Result for view @view display @display.', [
        '@view' => $view->id(),
        '@display' => $view->current_display,
      ]),
      'fields' => fn() => $type_fields,
    ]);

    return $types;
  }

}
