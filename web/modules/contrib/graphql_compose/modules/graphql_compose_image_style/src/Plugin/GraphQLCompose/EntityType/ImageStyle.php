<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_image_style\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "image_style",
 *   base_fields = {
 *     "name" = {},
 *   }
 * )
 */
class ImageStyle extends GraphQLComposeEntityTypeBase {

}
