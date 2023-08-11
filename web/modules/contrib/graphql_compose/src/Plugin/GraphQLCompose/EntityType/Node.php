<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * Define entity type.
 *
 * @GraphQLComposeEntityType(
 *   id = "node",
 *   prefix = "Node",
 *   base_fields = {
 *     "langcode" = {},
 *     "path" = {},
 *     "created" = {},
 *     "changed" = {},
 *     "status" = {},
 *     "promote" = {},
 *     "sticky" = {},
 *     "title" = {
 *       "field_type" = "entity_label",
 *     }
 *   }
 * )
 */
class Node extends GraphQLComposeEntityTypeBase {

}
