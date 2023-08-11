<?php

namespace Drupal\graphql_compose_extra_node_type\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "NodeType"
 * )
 */
class NodeType extends SchemaTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function toType(): Type
  {
    return new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Node Type field information.'),
      'fields' => fn () => [
        'id' => Type::nonNull(Type::id()),
        'name'  => Type::nonNull(Type::string()),
        'type'  => Type::nonNull(Type::string()),
      ],
    ]);
  }
}
