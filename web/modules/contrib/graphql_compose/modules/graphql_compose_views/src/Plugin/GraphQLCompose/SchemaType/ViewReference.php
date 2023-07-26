<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ViewReference",
 * )
 */
class ViewReference extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A reference to an embedded view'),
      'fields' => fn() => [
        'view' => Type::nonNull(Type::string()),
        'display' => Type::nonNull(Type::string()),

        // Extra metadata from the viewfield reference.
        'contextualFilter' => Type::listOf(Type::nonNull(Type::string())),
        'pageSize' => Type::int(),

        // Value add.
        'query' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The name of the query used to fetch the data, if the view is a GraphQL display.'),
        ],
      ],
    ]);

    return $types;
  }

}
