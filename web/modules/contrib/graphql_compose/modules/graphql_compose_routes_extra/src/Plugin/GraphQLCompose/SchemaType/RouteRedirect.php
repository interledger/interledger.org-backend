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
 *   id = "RouteRedirect"
 * )
 */
class RouteRedirect extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Redirect to another URL with status.'),
      'interfaces' => fn() => [
        static::type('Route'),
      ],
      'fields' => fn() => [
        'url' => Type::nonNull(Type::string()),
        'internal' => Type::nonNull(Type::boolean()),
        'status' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('Suggested status for redirect. Eg 301.'),
        ],
        'redirect' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Utility prop. Always true for redirects.'),
        ],
      ],
    ]);

    return $types;
  }

}
