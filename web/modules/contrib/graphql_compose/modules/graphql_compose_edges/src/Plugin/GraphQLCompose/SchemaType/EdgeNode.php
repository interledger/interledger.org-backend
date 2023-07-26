<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\graphql_compose_edges\EnabledBundlesTrait;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "EdgeNode",
 * )
 */
class EdgeNode extends GraphQLComposeSchemaTypeBase {

  use EnabledBundlesTrait;

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('This entity is accessible over an Edge connection.'),
      'fields' => fn() => [
        'id' => Type::nonNull(Type::id()),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   *
   * Add enabled types to the EdgeNode interface.
   */
  public function getExtensions(): array {

    $extensions = parent::getExtensions();

    foreach ($this->getEnabledBundlePlugins() as $bundle) {
      $extensions[] = new ObjectType([
        'name' => $bundle->getTypeSdl(),
        'interfaces' => fn() => [
          static::type('EdgeNode'),
        ],
      ]);
    }

    return $extensions;
  }

}
