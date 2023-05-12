<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes_extra\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "RouteUnion"
 * )
 */
class RouteUnion extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new UnionType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Route types that can exist in the system.'),
      'types' => fn() => [
        static::type('RouteInternal'),
        static::type('RouteExternal'),
        static::type('RouteRedirect'),
      ],
    ]);

    return $types;
  }

  /**
   * Disable automatic entity types.
   */
  public function getExtensions(): array {

    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'route' => [
          'type' => static::type('RouteUnion'),
          'description' => (string) $this->t('Load a Route by path.'),
          'args' => [
            'path' => [
              'type' => Type::nonNull(Type::string()),
              'description' => (string) $this->t('Internal path to load. Eg /about'),
            ],
            'langcode' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Filter the route by language. Eg en, ja, fr. Defaults to site default language.'),
              'defaultValue' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            ],
            'domain' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Domain to filter by'),
            ],
          ],
        ],
      ],
    ]);

    $extensions[] = new ObjectType([
      'name' => 'Link',
      'fields' => fn() => [
        'route' => static::type('RouteUnion'),
      ],
    ]);

    return $extensions;
  }

}
