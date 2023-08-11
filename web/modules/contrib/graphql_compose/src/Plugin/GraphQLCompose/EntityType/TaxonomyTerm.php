<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "taxonomy_term",
 *   type_sdl = "Term",
 *   prefix = "Term",
 *   base_fields = {
 *     "langcode" = {},
 *     "created" = {},
 *     "changed" = {},
 *     "path" = {},
 *     "status" = {},
 *     "parent" = {
 *       "required" = FALSE,
 *       "multiple" = FALSE,
 *     },
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
