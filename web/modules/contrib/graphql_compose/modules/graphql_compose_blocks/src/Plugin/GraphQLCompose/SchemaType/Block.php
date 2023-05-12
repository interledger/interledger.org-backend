<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Block"
 * )
 */
class Block extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Block field information.'),
      'fields' => fn() => [
        'id'     => Type::nonNull(Type::id()),
        'title'  => Type::string(),
        'render' => static::type('Html'),
      ],
    ]);

    return $types;
  }

}
