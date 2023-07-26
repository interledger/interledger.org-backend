<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_edges\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "weight",
 *   type_sdl = "Int"
 * )
 */
class Weight extends GraphQLComposeFieldTypeBase {

  use FieldProducerTrait;

}