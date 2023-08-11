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
 *   id = "TableRow",
 * )
 */
class TableRow extends GraphQLComposeSchemaTypeBase {

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
      'description' => (string) $this->t('A row of a table field.'),
      'fields' => fn() => [
        'data' => [
          'type' => Type::listOf(Type::string()),
          'description' => (string) $this->t('The data of the row.'),
        ],
        'weight' => [
          'type' => Type::int(),
          'description' => (string) $this->t('The weight of the row.'),
        ],
      ],
    ]);

    return $types;
  }

}
