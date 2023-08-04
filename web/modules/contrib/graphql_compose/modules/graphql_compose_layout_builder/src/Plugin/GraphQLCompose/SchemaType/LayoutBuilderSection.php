<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "LayoutBuilderSection"
 * )
 */
class LayoutBuilderSection extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A Layout Builder section defined by the CMS.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('Machine readable identifier of the section.'),
        ],
        'components' => [
          'type' => Type::listOf(Type::nonNull(static::type('LayoutBuilderComponent'))),
          'description' => (string) $this->t('A layout section component.'),
        ],
        'layout' => [
          'type' => Type::nonNull(static::type('Layout')),
          'description' => (string) $this->t('The layout for this section.'),
        ],
        'settings' => [
          'type' => Type::nonNull(static::type('Attributes')),
          'description' => (string) $this->t('The settings of the layout section.'),
        ],
        'weight' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('The delta position of the layout section.'),
        ],
      ],
    ]);

    return $types;
  }

}
