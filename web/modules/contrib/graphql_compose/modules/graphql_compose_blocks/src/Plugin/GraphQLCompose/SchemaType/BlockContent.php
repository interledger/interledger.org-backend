<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockContent"
 * )
 */
class BlockContent extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Block field information.'),
      'interfaces' => fn() => [
        static::type('Node'),
        static::type('Block'),
      ],
      'fields' => fn() => [
        'id'     => Type::nonNull(Type::id()),
        'title'  => Type::string(),
        'render' => static::type('Html'),
        'entity' => static::type('BlockContentUnion'),
      ],
    ]);

    return $types;
  }

}
