<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_layout_builder\EnabledBundlesTrait;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "LayoutBuilderInterface",
 * )
 */
class LayoutBuilderInterface extends GraphQLComposeSchemaTypeBase {

  use EnabledBundlesTrait;

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('This content has been arranged by a User using Layout Builder.'),
      'fields' => fn() => [
        'sections' => Type::listOf(Type::nonNull(static::type('LayoutBuilderSection'))),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $bundles = $this->getEnabledBundlePlugins();

    foreach ($bundles as $bundle) {

      // Add layout interface to entity.
      $extensions[] = new ObjectType([
        'name' => $bundle->getTypeSdl(),
        'interfaces' => fn() => [
          static::type('LayoutBuilderInterface'),
        ],
        'fields' => fn() => [
          'sections' => [
            'type' => Type::listOf(Type::nonNull(static::type('LayoutBuilderSection'))),
            'description' => (string) $this->t('This content has been arranged by a User using Layout Builder.'),
            'args' => [
              'viewMode' => [
                'type' => Type::nonNull(Type::id()),
                'description' => (string) $this->t('The view mode to return layout information for. Eg default, full, teaser'),
                'defaultValue' => 'default',
              ],
            ],
          ],
        ],
      ]);
    }

    return $extensions;
  }

}
