<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockUnion"
 * )
 */
class BlockUnion extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new UnionType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Block types that can exist in the system.'),
      'types' => fn() => [
        static::type('BlockPlugin'),
        static::type('BlockContent'),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   *
   * Disable automatic entity types.
   */
  public function getExtensions(): array {

    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'block' => [
          'type' => static::type('BlockUnion'),
          'description' => (string) $this->t('Load a Block plugin.'),
          'args' => [
            'id' => [
              'type' => Type::nonNull(Type::id()),
              'description' => (string) $this->t('Block plugin ID. Eg page_title_block, block_content:uuid'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }

}
