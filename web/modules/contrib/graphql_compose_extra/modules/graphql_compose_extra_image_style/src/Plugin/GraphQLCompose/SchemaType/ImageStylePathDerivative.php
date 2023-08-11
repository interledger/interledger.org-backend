<?php

namespace Drupal\graphql_compose_extra_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ImageStylePathDerivative",
 * )
 */
class ImageStylePathDerivative extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('ImageStylePath derivative for an Image.'),
      'fields' => fn () => [
        'url'    => Type::string(),
        'path'   => Type::string(),
        'width'  => Type::int(),
        'height' => Type::int(),
        'style'  => static::type('ImageStyle'),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array
  {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Image',
      'fields' => fn () => [
        'style' => [
          'type' => static::type('ImageStylePathDerivative'),
          'description' => (string) $this->t('Fetch an image style.'),
          'args' => [
            'name' => [
              'type' => static::type('ImageStylePathAvailable'),
              'description' => (string) $this->t('Image styles available via the CMS.'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }
}
