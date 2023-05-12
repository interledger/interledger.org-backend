<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_edges_extra\EnabledBundlesTrait;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Edge",
 * )
 */
class EdgeType extends GraphQLComposeSchemaTypeBase {

  use EnabledBundlesTrait;

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('An edge in a connection.
        Provides the cursor to fetch data based on the position of the associated node. Specific edge implementations may provide more information about the relationship they represent.
      '),
      'fields' => fn() => [
        'cursor' => Type::nonNull(static::type('Cursor')),
        'node' => Type::nonNull(static::type('Node')),
      ],
    ]);

    foreach ($this->getEnabledBundlePlugins() as $bundle) {
      $type_sdl = $bundle->getTypeSdl();

      $types[] = new ObjectType([
        'name' => $type_sdl . 'Edge',
        'description' => (string) $this->t('Edge for @bundle.', [
          '@bundle' => $type_sdl,
        ]),
        'interfaces' => fn() => [
          static::type('Edge'),
        ],
        'fields' => fn() => [
          'cursor' => Type::nonNull(static::type('Cursor')),
          'node' => Type::nonNull(static::type($type_sdl)),
        ],
      ]);
    }

    return $types;
  }

}
