<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_image_style\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "image_style",
 *   base_fields = {
 *     "uuid" = {
 *       "name_sdl" = "id",
 *       "type_sdl" = "ID",
 *       "required" = true,
 *     },
 *     "name" = {},
 *   }
 * )
 */
class ImageStyle extends GraphQLComposeEntityTypeBase {

}
