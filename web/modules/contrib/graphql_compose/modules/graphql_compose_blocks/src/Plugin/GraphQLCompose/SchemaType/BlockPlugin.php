<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockPlugin"
 * )
 */
class BlockPlugin extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("Block plugin is a modular piece of content that can be displayed in various regions of a website's layout."),
      'interfaces' => fn() => [
        static::type('BlockInterface'),
      ],
      'fields' => fn() => [
        'id' => Type::nonNull(Type::id()),
        'title' => Type::string(),
        'render' => static::type('Html'),
      ],
    ]);

    return $types;
  }

}
