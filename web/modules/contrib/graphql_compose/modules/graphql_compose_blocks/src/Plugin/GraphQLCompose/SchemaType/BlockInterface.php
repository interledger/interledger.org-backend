<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockInterface"
 * )
 */
class BlockInterface extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("Blocks are a modular piece of content that can be displayed in various regions of a website's layout."),
      'fields' => fn() => [
        'id' => [
          'type' => Type::nonNull(Type::id()),
          'description' => (string) $this->t('The Universally Unique IDentifier (UUID).'),
        ],
        'title' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The title of the block if provided.'),
        ],
        'render' => [
          'type' => static::type('Html'),
          'description' => (string) $this->t('The rendered output of the block.'),
        ],
      ],
    ]);

    return $types;
  }

}
