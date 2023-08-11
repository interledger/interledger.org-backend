<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\system\MenuInterface;
use GraphQL\Type\Definition\EnumType;

use function Symfony\Component\String\u;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MenuAvailable"
 * )
 */
class MenuAvailable extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $settings = $this->configFactory->get('graphql_compose.settings');

    $menus = array_filter(
      $this->entityTypeManager->getStorage('menu')->loadMultiple(),
      fn (MenuInterface $menu) => $settings->get('entity_config.menu.' . $menu->id() . '.enabled') ?: FALSE
    );

    $values = [];
    foreach ($menus as $menu) {
      $id = u($menu->id())
        ->snake()
        ->upper()
        ->toString();

      $values[$id] = [
        'value' => $menu->id(),
        'description' => (string) $menu->label(),
      ];
    }

    $undefined = [
      'UNDEFINED' => [
        'value' => 'undefined',
        'description' => (string) $this->t('No menus have been enabled.'),
      ],
    ];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('List of menus available to load.'),
      'values' => $values ?: $undefined,
    ]);

    return $types;
  }

}
