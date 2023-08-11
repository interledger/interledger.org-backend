<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "block_content",
 *   prefix = "BlockContent",
 *   base_fields = {
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
