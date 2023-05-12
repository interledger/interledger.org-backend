<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteExternal"
 * )
 */
class RouteExternal extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Route outside of this website.'),
      'interfaces' => fn() => [
        static::type('Route'),
      ],
      'fields' => fn() => [
        'url' => Type::nonNull(Type::string()),
        'internal' => Type::nonNull(Type::boolean()),
      ],
    ]);

    return $types;
  }

}
