<?php

namespace Drupal\graphql_compose_extra_responsive_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ResponsiveImageStyleDerivative",
 * )
 */
class ResponsiveImageStyleDerivative extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('ResponsiveImageStyle derivative for an Image.'),
      'fields' => fn () => [
        'name' => Type::nonNull(Type::string()),
        'url' => Type::nonNull(Type::string()),
        'srcSet' => Type::nonNull(Type::string()),
        'width' => Type::nonNull(Type::int()),
        'height' => Type::nonNull(Type::int()),
      ],
    ]);

    $types[] = new ObjectType([
      'name' => $this->getPluginId() . 'Deprecated',
      'description' => (string) $this->t('ResponsiveImageStyle derivative for an Image (Deprecated).'),
      'fields' => fn () => [
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
   * {@inheritdoc}
   */
  public function getExtensions(): array
  {
    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Image',
      'fields' => fn () => [
        'responsiveVariations' => [
          'type' => Type::listOf(Type::nonNull(static::type('ResponsiveImageStyleDerivative'))),
          'description' => (string) $this->t('Responsive Image variations control different sizes and formats for images.'),
          'args' => [
            'styles' => Type::listOf(static::type('ResponsiveImageStyleAvailable')),
          ],
        ],
        'responsive' => [
          'type' => static::type('ResponsiveImageStyleDerivativeDeprecated'),
          'description' => (string) $this->t('Fetch an responsive image style. (Deprecated)'),
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
