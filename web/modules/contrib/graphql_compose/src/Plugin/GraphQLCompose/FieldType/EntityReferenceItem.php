<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldUnionInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldUnionTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "entity_reference"
 * )
 */
class EntityReferenceItem extends GraphQLComposeFieldTypeBase implements FieldUnionInterface {

  use FieldUnionTrait;
  use FieldProducerTrait;

  /**
   * Value to return to getProducerProperty in producer trait.
   *
   * @var string
   */
  public $producerProperty = 'entity';

}
