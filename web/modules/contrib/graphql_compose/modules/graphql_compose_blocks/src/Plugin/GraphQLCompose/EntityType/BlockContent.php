<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "block_content",
 *   interfaces = { "Node"},
 *   prefix = "BlockContent",
 *   base_fields = {
 *     "uuid" = {},
 *     "langcode" = {},
 *     "created" = {},
 *     "changed" = {},
 *     "info" = {
 *       "field_type" = "entity_label",
 *       "name_sdl" = "title",
 *     },
 *     "reusable" = {}
 *   }
 * )
 */
class BlockContent extends GraphQLComposeEntityTypeBase {

}
