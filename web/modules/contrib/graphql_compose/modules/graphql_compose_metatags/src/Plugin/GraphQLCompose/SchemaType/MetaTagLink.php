<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MetaTagLink",
 * )
 */
class MetaTagLink extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'interfaces' => fn() => [
        static::type('MetaTag'),
      ],
      'fields' => fn() => [
        'tag' => Type::nonNull(Type::string()),
        'attributes' => Type::nonNull(static::type('MetaTagLinkAttributes')),
      ],
    ]);

    return $types;
  }

}
