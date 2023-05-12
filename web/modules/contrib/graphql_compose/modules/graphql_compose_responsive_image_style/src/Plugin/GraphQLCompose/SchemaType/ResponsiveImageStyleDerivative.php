<?php

namespace Drupal\graphql_compose_responsive_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ResponsiveImageStyleDerivative",
 * )
 */
class ResponsiveImageStyleDerivative extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('ResponsiveImageStyle derivative for an Image.'),
      'fields' => fn() => [
        'path'   => Type::string(),
        'srcSetPath'   => Type::string(),
        'width'  => Type::int(),
        'height' => Type::int(),
        'responsive'  => static::type('ResponsiveImageStyle'),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritDoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Image',
      'fields' => fn() => [
        'responsive' => [
          'type' => static::type('ResponsiveImageStyleDerivative'),
          'description' => (string) $this->t('Fetch an responsive image style.'),
          'args' => [
            'name' => [
              'type' => static::type('ResponsiveImageStyleAvailable'),
              'description' => (string) $this->t('Responsive Image styles available via the CMS.'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }

}
