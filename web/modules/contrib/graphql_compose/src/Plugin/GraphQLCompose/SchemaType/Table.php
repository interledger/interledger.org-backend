<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Table",
 * )
 */
class Table extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'fields' => fn() => [
        'caption' => Type::string(),
        'rows'    => Type::listOf(static::type('TableRow')),
        'format'  => [
          'type' => Type::string(),
          'description' => (string) $this->t('A text format associated with the row data.'),
        ],
      ],
    ]);

    return $types;
  }

}
