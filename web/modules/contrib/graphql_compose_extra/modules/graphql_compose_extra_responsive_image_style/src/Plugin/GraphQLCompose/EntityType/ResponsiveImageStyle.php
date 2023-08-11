<?php

namespace Drupal\graphql_compose_extra_responsive_image_style\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "responsive_image_style",
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
class ResponsiveImageStyle extends GraphQLComposeEntityTypeBase {

}
