<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * Use a property item when using something
 * like a config entity (Menu) that doesn't have fields.
 *
 * Theres no access on these properties.
 *
 * @GraphQLComposeFieldType(
 *   id = "property",
 * )
 */
class PropertyItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $compose = $builder->compose();

    $compose->add(
      $builder->produce('property_path')
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue($this->getFieldName()))
        ->map('type', $builder->fromValue('entity:' . $this->getEntityWrapper()->entity->id()))
    );

    return $compose;
  }

}
