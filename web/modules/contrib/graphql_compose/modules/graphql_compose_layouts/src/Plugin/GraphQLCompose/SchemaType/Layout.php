<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layouts\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Layout"
 * )
 */
class Layout extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A layout defined by the CMS.'),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('Machine readable name of the layout definition.'),
        ],
        'label' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('Human readable name of the layout definition.'),
        ],
        'category' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('Human readable category of the layout definition.'),
        ],
        'regions' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
          'description' => (string) $this->t('Regions represent where a User can place content within the CMS.'),
        ],
        'defaultRegion' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('A default region as fallback for convenience.'),
        ],
      ],
    ]);

    return $types;
  }

}
