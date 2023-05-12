<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "uuid",
 *   name_sdl = "id",
 *   type_sdl = "ID"
 * )
 */
class UuidItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $compose = $builder->compose();

    $compose->add($builder->produce('entity_uuid')
      ->map('entity', $builder->fromParent())
    );

    return $compose;
  }

}
