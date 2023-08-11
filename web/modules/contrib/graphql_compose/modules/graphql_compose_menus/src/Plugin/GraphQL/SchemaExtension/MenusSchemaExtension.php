<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function Symfony\Component\String\u;

/**
 * Add menus to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_menus_schema",
 *   name = "GraphQL Compose Menus",
 *   description = @Translation("Add menus to the Schema."),
 *   schema = "graphql_compose"
 * )
 */
class MenusSchemaExtension extends SdlSchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver(
      'Query',
      'menu',
      $builder->compose(
        $builder->produce('entity_load')
          ->map('type', $builder->fromValue('menu'))
          ->map('id',
            $builder->produce('schema_enum_value')
              ->map('type', $builder->fromValue('MenuAvailable'))
              ->map('value', $builder->fromArgument('name')),
          )
      )
    );

    // Menu name.
    $registry->addFieldResolver('Menu', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
      );

    // Menu items.
    $registry->addFieldResolver('Menu', 'items',
      $builder->produce('menu_links')
        ->map('menu', $builder->fromParent())
      );

    // Menu link UUID.
    $registry->addFieldResolver('MenuItem', 'id',
      $builder->compose(
        $builder->produce('menu_tree_link')->map('element', $builder->fromParent()),
        $builder->callback(fn(MenuLinkInterface $link) => $link->getDerivativeId() ?: $link->getPluginId()),
      )
    );

    // Menu title.
    $registry->addFieldResolver('MenuItem', 'title',
      $builder->produce('menu_link_label')
        ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent()))
      );

    // Menu description.
    $registry->addFieldResolver('MenuItem', 'description',
      $builder->produce('menu_link_description')
        ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent()))
    );

    // Menu url.
    $registry->addFieldResolver('MenuItem', 'url',
      $builder->compose(
        $builder->produce('menu_link_url')
          ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent())),

        $builder->produce('url_path')
          ->map('url', $builder->fromParent()),
      )
    );

    // Menu internal.
    $registry->addFieldResolver('MenuItem', 'internal',
      $builder->compose(
        $builder->produce('menu_link_url')
          ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent())),

        $builder->callback(fn(Url $url) => $url->isRouted()),
      )
    );

    // Menu expanded.
    $registry->addFieldResolver('MenuItem', 'expanded',
      $builder->produce('menu_link_expanded')
        ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent()))
    );

    // Menu children.
    $registry->addFieldResolver('MenuItem', 'children',
      $builder->produce('menu_tree_subtree')
        ->map('element', $builder->fromParent())
    );

    // Menu children.
    $registry->addFieldResolver('MenuItem', 'attributes',
      $builder->produce('menu_tree_link')->map('element', $builder->fromParent()),
    );

    // Menu attributes.
    $attributes = ['class' => 'class'];
    if ($this->moduleHandler->moduleExists('menu_link_attributes')) {
      $menu_link_attributes = $this->configFactory->get('menu_link_attributes.config')->get('attributes') ?: [];
      foreach (array_keys($menu_link_attributes) as $menu_link_attribute) {
        $attr = u($menu_link_attribute)->camel()->toString();
        $attributes[$attr] = $attr;
      }
    }

    foreach ($attributes as $attr) {
      $registry->addFieldResolver('MenuItemAttributes', $attr,
        $builder->produce('menu_link_attribute')
          ->map('link', $builder->fromParent())
          ->map('attribute', $builder->fromValue($attr))
      );
    }

    // Menu route.
    // This is toggled by users.
    $registry->addFieldResolver('MenuItem', 'route',

      $builder->compose(
        $builder->produce('menu_tree_link')
          ->map('element', $builder->fromParent()),

        $builder->cond([
          [
            $builder->callback(function (MenuLinkInterface $link) {
              return static::menuRouteEnabled($link->getMenuName());
            }),
            $builder->fromParent(),
          ], [
            $builder->fromValue(TRUE),
            $builder->fromValue(NULL),
          ],
        ]),

        $builder->produce('menu_link_url')
          ->map('link', $builder->fromParent()),

        $builder->produce('url_path')
          ->map('url', $builder->fromParent()),

        $builder->produce('url_or_redirect')
          ->map('path', $builder->fromParent())
      )
    );
  }

  /**
   * Check wether a user has enabled route resolution on a menu.
   *
   * @param string $name
   *   The menu name.
   *
   * @return bool
   *   Whether the menu route is enabled.
   */
  public static function menuRouteEnabled(string $name): bool {

    // Reduce slamming the config system.
    $enabled = &drupal_static(__METHOD__);
    if (!isset($enabled[$name])) {
      $settings = \Drupal::config('graphql_compose.settings');
      $enabled[$name] = $settings->get('entity_config.menu.' . $name . '.menu_route_enabled') ?: FALSE;
    }

    return $enabled[$name];
  }

}
