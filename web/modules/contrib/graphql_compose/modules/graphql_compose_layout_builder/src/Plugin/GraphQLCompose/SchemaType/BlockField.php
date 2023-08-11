<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_layout_builder\EnabledBundlesTrait;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritdoc}
 *
 * Changing FieldBlock to BlockField to avoid naming it BlockFieldBlock.
 *
 * @GraphQLComposeSchemaType(
 *   id = "BlockField"
 * )
 */
class BlockField extends GraphQLComposeSchemaTypeBase {

  use EnabledBundlesTrait;

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("A block field is a modular field property that can be displayed in various regions of a website's layout."),
      'interfaces' => fn() => [
        static::type('BlockInterface'),
      ],
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
        'field' => [
          'type' => Type::nonNull(static::type('BlockFieldUnion')),
          'description' => (string) $this->t('The entity field to be displayed within the block.'),
        ],
        'fieldName' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The name of the field property to be displayed within the block.'),
        ],
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   *
   * Pump our new types into the BlockUnion. Unf.
   */
  public function getExtensions(): array {

    $extensions = parent::getExtensions();

    $bundles = $this->getEnabledBundlePlugins();

    if ($bundles) {
      $extensions[] = new UnionType([
        'name' => 'BlockUnion',
        'types' => [
          static::type('BlockField'),
        ],
      ]);
    }

    return $extensions;
  }

}
