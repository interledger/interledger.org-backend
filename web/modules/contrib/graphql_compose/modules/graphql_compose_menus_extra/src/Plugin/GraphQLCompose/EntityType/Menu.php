<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_menus_extra\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "menu",
 *   base_fields = {
 *     "name" = {},
 *     "items" = {
 *       "type_sdl" = "MenuItem",
 *       "multiple" = TRUE
 *     },
 *   }
 * )
 */
class Menu extends GraphQLComposeEntityTypeBase {

  /**
   * Force add a query for menus to the base Query.
   */
  public function registerTypes(): void {
    parent::registerTypes();

    $extension = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'menu' => [
          'type' => $this->gqlSchemaTypeManager->get('Menu'),
          'description' => (string) $this->t('Load a Route by path.'),
          'args' => [
            'name' => [
              'type' => Type::nonNull($this->gqlSchemaTypeManager->get('MenuAvailable')),
              'description' => (string) $this->t('Internal menu name. Eg main'),
            ],
          ],
        ],
      ],
    ]);

    $this->gqlSchemaTypeManager->extend($extension);
  }

  /**
   * Disable automatic entity resolvers.
   *
   * Menus are resolved within MenusSchemaExtension.
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {

  }

}
