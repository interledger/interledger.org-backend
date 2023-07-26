<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use function Symfony\Component\String\u;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MenuItemAttributes"
 * )
 */
class MenuItemAttributes extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Menu item options set within the CMS.'),
      'fields' => function () {

        $fields = ['class' => Type::string()];

        if ($this->moduleHandler->moduleExists('menu_link_attributes')) {
          $menu_link_attributes = $this->configFactory
            ->get('menu_link_attributes.config')
            ->get('attributes') ?: [];

          foreach ($menu_link_attributes as $key => $value) {
            $description = $value['description']
            ?: (string) $this->t('Menu item attribute @key.', ['@key' => $key]);

            $fields[u($key)->camel()->toString()] = [
              'type' => Type::string(),
              'description' => $description,
            ];
          }
        }

        return $fields;
      },
    ]);

    return $types;
  }

}
