<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "string_long",
 *   type_sdl = "String"
 * )
 */
class StringLongItem extends GraphQLComposeFieldTypeBase {

  use FieldProducerTrait;

}
