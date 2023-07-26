<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "media",
 *   prefix = "Media",
 *   base_fields = {
 *     "created" = {},
 *     "changed" = {},
 *     "status" = {},
 *     "name" = {
 *       "field_type" = "entity_label"
 *     }
 *   }
 * )
 */
class Media extends GraphQLComposeEntityTypeBase {

}
