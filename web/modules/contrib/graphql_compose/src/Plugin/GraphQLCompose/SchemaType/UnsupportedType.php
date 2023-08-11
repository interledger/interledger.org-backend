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
 *   id = "UnsupportedType",
 * )
 */
class UnsupportedType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('
        Unsupported entity or field type in the schema.
        This entity may not have been enabled in the schema yet and is being referenced via entity reference.
      '),
      'fields' => fn() => [
        'unsupported' => [
          'type' => Type::boolean(),
          'description' => (string) $this->t('Unsupported type, always TRUE.'),
        ],
      ],
    ]);

    return $types;
  }

}
