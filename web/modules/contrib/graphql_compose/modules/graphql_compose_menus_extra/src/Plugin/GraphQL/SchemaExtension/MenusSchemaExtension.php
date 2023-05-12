<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus_extra\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Add menus to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_menus_extra_schema",
 *   name = "GraphQL Compose Menus",
 *   description = "Add menus to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class MenusSchemaExtension extends SdlSchemaExtensionPluginBase {

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
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:menu'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('label'))
      );

    // Menu items.
    $registry->addFieldResolver('Menu', 'items',
      $builder->produce('menu_links')
        ->map('menu', $builder->fromParent())
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

    // Menu expanded.
    $registry->addFieldResolver('MenuItem', 'expanded',
      $builder->produce('menu_link_expanded')
        ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent()))
    );

    // Menu url.
    $registry->addFieldResolver('MenuItem', 'route',
      $builder->compose(
        $builder->produce('menu_link_url')
          ->map('link', $builder->produce('menu_tree_link')->map('element', $builder->fromParent())),
        $builder->produce('url_or_redirect')
          ->map('url', $builder->fromParent())
      )
    );

    // Menu children.
    $registry->addFieldResolver('MenuItem', 'children',
      $builder->produce('menu_tree_subtree')
        ->map('element', $builder->fromParent())
      );
  }

}
