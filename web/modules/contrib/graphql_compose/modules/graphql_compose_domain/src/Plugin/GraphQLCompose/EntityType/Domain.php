<?php

namespace Drupal\graphql_compose_domain\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\EntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "domain",
 *   interfaces = { "Node", "Domain" },
 *   base_fields = {
 *     "uuid" = {},
 *     "name" = {}
 *   }
 * )
 */
class Domain extends EntityTypeBase
{
}
