<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ConnectionPageInfo",
 * )
 */
class ConnectionPageInfo extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Information about the page in a connection.'),
      'fields' => fn() => [
        'hasNextPage' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether there are more pages in this connection.'),
        ],
        'hasPreviousPage' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether there are previous pages in this connection.'),
        ],
        'startCursor' => [
          'type' => Type::nonNull(static::type('Cursor')),
          'description' => (string) $this->t('The cursor for the first element in this page.'),
        ],
        'endCursor' => [
          'type' => Type::nonNull(static::type('Cursor')),
          'description' => (string) $this->t('The cursor for the last element in this page.'),
        ],
      ],
    ]);

    return $types;
  }

}
