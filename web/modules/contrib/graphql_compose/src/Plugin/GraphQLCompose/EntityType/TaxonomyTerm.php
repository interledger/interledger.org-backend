<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "taxonomy_term",
 *   interfaces = { "Node" },
 *   type_sdl = "Term",
 *   prefix = "Term",
 *   base_fields = {
 *     "uuid" = {},
 *     "langcode" = {},
 *     "created" = {},
 *     "changed" = {},
 *     "path" = {},
 *     "status" = {},
 *     "name" = {
 *       "field_type" = "entity_label",
 *     },
 *     "description" = {
 *       "field_type" = "text"
 *     },
 *   }
 * )
 */
class TaxonomyTerm extends GraphQLComposeEntityTypeBase {

}
