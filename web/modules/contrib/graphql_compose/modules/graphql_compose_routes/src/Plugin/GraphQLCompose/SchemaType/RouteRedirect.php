<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteRedirect"
 * )
 */
class RouteRedirect extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Redirect to another URL with status.'),
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
        'status' => [
          'type' => Type::nonNull(Type::int()),
          'description' => (string) $this->t('Suggested status for redirect. Eg 301.'),
        ],
        'redirect' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Utility prop. Always true for redirects.'),
        ],
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    if ($this->moduleHandler->moduleExists('redirect')) {
      $extensions[] = new UnionType([
        'name' => 'RouteUnion',
        'types' => [
          static::type('RouteRedirect'),
        ],
      ]);
    }

    return $extensions;
  }

}
