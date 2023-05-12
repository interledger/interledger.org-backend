<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;

/**
 * Generic trait for fetching a property value from a field.
 */
trait FieldProducerTrait {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    return $builder->compose(
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue($this->getFieldName())),

      // Useful for extending this producer.
      $builder->context('field_value', $builder->fromParent()),

      $builder->produce('field_type_plugin')
        ->map('plugin', $builder->fromValue($this))
        ->map('value', $builder->fromParent())
    );
  }

}
