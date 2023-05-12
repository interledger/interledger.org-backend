<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldUnionTrait;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "entity_reference"
 * )
 */
class EntityReferenceItem extends GraphQLComposeFieldTypeBase {

  use FieldUnionTrait;
  use FieldProducerTrait;

  /**
   * Value to return to getProducerProperty in producer trait.
   *
   * @var string
   */
  public $producerProperty = 'entity';

}
