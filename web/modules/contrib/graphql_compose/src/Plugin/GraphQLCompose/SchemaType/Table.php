<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Table",
 * )
 */
class Table extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('tablefield')) {
      return [];
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A table field.'),
      'fields' => fn() => [
        'caption' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The caption of the table.'),
        ],
        'rows' => [
          'type' => Type::listOf(static::type('TableRow')),
          'description' => (string) $this->t('The rows of the table.'),
        ],
        'format' => [
          'type' => Type::string(),
          'description' => (string) $this->t('A text format associated with the row data.'),
        ],
      ],
    ]);

    return $types;
  }

}
