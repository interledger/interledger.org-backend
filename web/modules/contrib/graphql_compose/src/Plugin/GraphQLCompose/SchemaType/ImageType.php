<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Image",
 * )
 */
class ImageType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A image object to represent an managed file.'),
      'fields' => fn() => [
        'url' => Type::nonNull(Type::string()),
        'width' => Type::nonNull(Type::int()),
        'height' => Type::nonNull(Type::int()),
        'alt' => Type::string(),
        'title' => Type::string(),
        'mime' => Type::string(),
      ],
    ]);

    return $types;
  }

}
