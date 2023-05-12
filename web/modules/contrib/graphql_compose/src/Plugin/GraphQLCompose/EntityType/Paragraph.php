<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "paragraph",
 *   interfaces = { "Node" },
 *   prefix = "Paragraph",
 *   base_fields = {
 *     "uuid" = {},
 *     "created" = {},
 *     "changed" = {},
 *   }
 * )
 */
class Paragraph extends GraphQLComposeEntityTypeBase {

}
