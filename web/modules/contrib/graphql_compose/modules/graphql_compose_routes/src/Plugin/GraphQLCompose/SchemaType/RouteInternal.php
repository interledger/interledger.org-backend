<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteInternal"
 * )
 */
class RouteInternal extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Route within this website.'),
      'interfaces' => fn() => [
        static::type('Route'),
      ],
      'fields' => fn() => [
        'url' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('URL of this route.'),
        ],
        'internal' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether this route is internal or external.'),
        ],
        'breadcrumbs' => [
          'type' => Type::listOf(Type::nonNull(static::type('Link'))),
          'description' => (string) $this->t('Breadcrumb links for this route.'),
        ],
        'entity' => [
          'type' => static::type('RouteEntityUnion'),
          'description' => (string) $this->t('Content assigned to this route.'),
        ],
      ],
    ]);

    return $types;
  }

}
