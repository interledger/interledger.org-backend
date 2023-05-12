<?php

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "NodesUnionConnection",
 * )
 */
class NodesUnionConnectionType extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritDoc}
   *
   * Create bundle connection.
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A paginated set of results.'),
      'fields' => fn() => [
        'edges' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('Edge')))),
          'description' => (string) $this->t('The edges of this connection.'),
        ],
        'nodes' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('NodeUnion')))),
          'description' => (string) $this->t('The nodes of the edges of this connection.'),
        ],
        'pageInfo' => [
          'type' => Type::nonNull(static::type('ConnectionPageInfo')),
          'description' => (string) $this->t('Information to aid in pagination.'),
        ],
      ],
    ]);

    $types[] = new ObjectType([
      'name' => 'NodesConnection',
      'description' => (string) $this->t('A paginated set of results for Nodes'),
      'interfaces' => fn () => [
        static::type('NodesUnionConnection'),
      ],
      'fields' => fn () => [
        'edges' => Type::nonNull(Type::listOf(Type::nonNull(static::type('Edge')))),
        'nodes' => Type::nonNull(Type::listOf(Type::nonNull(static::type('NodeUnion')))),
        'pageInfo' => Type::nonNull(static::type('ConnectionPageInfo')),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritDoc}
   *
   * Create bundle queries.
   */
  public function getExtensions(): array
  {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn () => [
        'nodes' => [
          'type' => Type::nonNull(static::type('NodesConnection')),
          'description' => (string) $this->t('List of all Nodes on the platform. Results are access controlled.'),
          'args' => [
            'after' => [
              'type' => static::type('Cursor'),
              'description' => (string) $this->t('Returns the elements that come after the specified cursor.'),
            ],
            'before' => [
              'type' => static::type('Cursor'),
              'description' => (string) $this->t('Returns the elements that come before the specified cursor.'),
            ],
            'first' => [
              'type' => Type::int(),
              'description' => (string) $this->t('Returns up to the first n elements from the list.'),
            ],
            'last' => [
              'type' => Type::int(),
              'description' => (string) $this->t('Returns up to the last n elements from the list.'),
            ],
            'reverse' => [
              'type' => Type::boolean(),
              'defaultValue' => FALSE,
              'description' => (string) $this->t('Reverse the order of the underlying list.'),
            ],
            'sortKey' => [
              'type' => static::type('ConnectionSortKeys'),
              'description' => (string) $this->t('Sort the underlying list by the given key.'),
            ],
            'filter' => [
              'type' => static::type('ConnectionFilter'),
              'description' => (string) $this->t('Filter entities.'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }
}
