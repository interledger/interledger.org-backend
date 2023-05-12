<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Route",
 * )
 */
class Route extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Routes represent incoming requests that resolve to content.'),
      'fields' => fn() => [
        'url' => Type::nonNull(Type::string()),
        'internal' => Type::nonNull(Type::boolean()),
      ],
    ]);

    return $types;
  }

}
