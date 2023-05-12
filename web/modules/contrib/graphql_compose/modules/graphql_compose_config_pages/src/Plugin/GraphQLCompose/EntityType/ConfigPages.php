<?php

namespace Drupal\graphql_compose_config_pages\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "config_pages",
 *   interfaces = { "Node", "ConfigPages" },
 *   base_fields = {
 *     "uuid" = {},
 *     "label" = {}
 *   }
 * )
 */
class ConfigPages extends GraphQLComposeEntityTypeBase
{
}
