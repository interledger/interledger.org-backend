<?php

namespace Drupal\graphql_compose_node_type\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "node_type",
 *   base_fields = {
 *     "name" = {
 *       "field_type" = "entity_label",
 *     },
 *     "type" = {}
 *   }
 * )
 */
class NodeType extends GraphQLComposeEntityTypeBase
{
}
