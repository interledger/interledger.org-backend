<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "path",
 *   type_sdl = "String"
 * )
 */
class PathItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $compose = $builder->compose();

    $compose->add($builder->produce('entity_url')
      ->map('entity', $builder->fromParent())
    );

    $compose->add($builder->produce('url_path')
      ->map('url', $builder->fromParent())
    );

    return $compose;
  }

}
