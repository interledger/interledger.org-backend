<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Node",
 * )
 */
class NodeType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A concrete fetchable type that is addressable by an id.'),
      'fields' => fn() => [
        'id' => Type::nonNull(Type::id()),
      ],
    ]);

    return $types;
  }

}
