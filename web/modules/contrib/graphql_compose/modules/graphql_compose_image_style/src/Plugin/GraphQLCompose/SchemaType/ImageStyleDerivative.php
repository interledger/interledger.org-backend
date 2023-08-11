<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ImageStyleDerivative",
 * )
 */
class ImageStyleDerivative extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('ImageStyle derivatives for an Image.'),
      'fields' => fn() => [
        'name' => Type::nonNull(Type::string()),
        'url' => Type::nonNull(Type::string()),
        'width' => Type::nonNull(Type::int()),
        'height' => Type::nonNull(Type::int()),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Image',
      'fields' => fn() => [
        'variations' => [
          'type' => Type::listOf(Type::nonNull(static::type('ImageStyleDerivative'))),
          'description' => (string) $this->t('Image variations control different sizes and formats for images.'),
          'args' => [
            'styles' => Type::listOf(static::type('ImageStyleAvailable')),
          ],
        ],
      ],
    ]);

    return $extensions;
  }

}
