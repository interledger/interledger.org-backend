<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_users\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;
use GraphQL\Type\Definition\ObjectType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "user",
 *   base_fields = {
 *     "created" = {},
 *     "changed" = {},
 *     "path" = {},
 *     "name" = {
 *       "field_type" = "entity_label",
 *     },
 *     "mail" = {
 *       "field_type" = "email",
 *       "required" = FALSE
 *     },
 *     "status" = {
 *       "field_type" = "user_status"
 *     },
 *     "roles" = {
 *       "field_type" = "user_roles",
 *       "required" = FALSE
 *     }
 *   }
 * )
 */
class User extends GraphQLComposeEntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public function registerTypes(): void {
    parent::registerTypes();

    // Add viewer to base Query.
    $viewer = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'viewer' => [
          'type' => $this->gqlSchemaTypeManager->get('User'),
          'description' => (string) $this->t('Get information about the currently authenticated user. NULL if not logged in.'),
        ],
      ],
    ]);

    $this->gqlSchemaTypeManager->extend($viewer);
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    parent::registerResolvers($registry, $builder);

    // Resolve viewer on base Query.
    $registry->addFieldResolver(
      'Query',
      'viewer',
      $builder->produce('viewer')
    );
  }

}
