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
 *   id = "LayoutBuilderComponent"
 * )
 */
class LayoutBuilderComponent extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A layout section component contains blocks of content.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('Machine readable name of the layout definition.'),
        ],
        'block' => [
          'type' => Type::nonNull(static::type('BlockUnion')),
          'description' => (string) $this->t('Blocks of content for the section component.'),
        ],
        'configuration' => [
          'type' => Type::nonNull(static::type('Attributes')),
          'description' => (string) $this->t('Any configuration for the component.'),
        ],
        'region' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The region of the layout section component.'),
        ],
        'weight' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('The weight position of the layout section component.'),
        ],
      ],
    ]);

    return $types;
  }

}
