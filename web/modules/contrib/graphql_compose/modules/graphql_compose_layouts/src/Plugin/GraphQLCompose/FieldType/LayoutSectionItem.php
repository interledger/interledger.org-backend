<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layouts\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "layout_section",
 *   type_sdl = "LayoutSection"
 * )
 */
class LayoutSectionItem extends GraphQLComposeFieldTypeBase {

  use FieldProducerTrait;

}
