<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteInternal"
 * )
 */
class RouteInternal extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Route within this website.'),
      'interfaces' => fn() => [
        static::type('Route'),
      ],
      'fields' => fn() => [
        'url' => Type::nonNull(Type::string()),
        'internal' => Type::nonNull(Type::boolean()),
        'entity' => [
          'type' => static::type('RouteEntityUnion'),
          'description' => (string) $this->t('Content assigned to this route.'),
        ],
      ],
    ]);

    return $types;
  }

}
