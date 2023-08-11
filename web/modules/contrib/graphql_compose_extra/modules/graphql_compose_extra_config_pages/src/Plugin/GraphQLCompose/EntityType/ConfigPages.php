<?php

namespace Drupal\graphql_compose_extra_config_pages\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "config_pages",
 *   interfaces = { "ConfigPages" },
 *   base_fields = {
 *     "uuid" = {},
 *     "label" = {}
 *   }
 * )
 */
class ConfigPages extends GraphQLComposeEntityTypeBase
{
}
