<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MenuItem"
 * )
 */
class MenuItem extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A menu item defined in the CMS.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The Universally Unique IDentifier (UUID).'),
        ],
        'title' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The title of the menu item.'),
        ],
        'description' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The description of the menu item.'),
        ],
        'url' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The URL of the menu item.'),
        ],
        'internal' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether this menu item links to an internal route.'),
        ],
        'expanded' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether this menu item is intended to be expanded.'),
        ],
        'attributes' => [
          'type' => Type::nonNull(static::type('MenuItemAttributes')),
          'description' => (string) $this->t('Attributes of this menu item.'),
        ],
        'children' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('MenuItem')))),
          'description' => (string) $this->t('Child menu items of this menu item.'),
        ],
        'route' => [
          'type' => static::type('RouteUnion'),
          'description' => (string) $this->t('The route this menu item uses. Route loading can be disabled per menu type.'),
        ],
      ],
    ]);

    return $types;
  }

}
