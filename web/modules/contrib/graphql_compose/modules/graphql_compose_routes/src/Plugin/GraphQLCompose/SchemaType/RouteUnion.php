<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteUnion"
 * )
 */
class RouteUnion extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new UnionType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Route types that can exist in the system.'),
      'types' => fn() => [
        static::type('RouteInternal'),
        static::type('RouteExternal'),
      ],
    ]);

    return $types;
  }

}
