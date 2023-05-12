<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MenuItem"
 * )
 */
class MenuItem extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A menu item defined in the CMS.'),
      'fields' => fn() => [
        'title'       => Type::string(),
        'description' => Type::string(),
        'expanded'    => Type::nonNull(Type::boolean()),
        'children'    => Type::listOf(Type::nonNull(static::type('MenuItem'))),
        'route'       => Type::nonNull(static::type('RouteUnion')),
      ],
    ]);

    return $types;
  }

}
