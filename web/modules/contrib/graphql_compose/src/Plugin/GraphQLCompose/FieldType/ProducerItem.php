<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * A choose your own adventure field type.
 * Useful for custom property fields.
 *
 * @GraphQLComposeFieldType(
 *   id = "producer",
 * )
 */
class ProducerItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    $producer = $this->configuration['producer'] ?? $builder->callback(fn () => NULL);

    return ($producer instanceof Composite) ? $producer : $builder->compose($producer);
  }

}
