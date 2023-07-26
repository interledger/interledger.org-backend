<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_users\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "entity_owner",
 *   description = @Translation("The author of this entity."),
 *   type_sdl = "User"
 * )
 */
class EntityOwner extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $compose = $builder->compose();

    $compose->add($builder->produce('entity_owner')
      ->map('entity', $builder->fromParent())
    );

    return $compose;
  }

}
