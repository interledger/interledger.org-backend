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
 *   id = "user_status",
 *   type_sdl = "UserStatus"
 * )
 */
class UserStatus extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $compose = $builder->compose();

    $compose->add($builder->produce('user_status')
      ->map('user', $builder->fromParent())
    );

    return $compose;
  }

}
