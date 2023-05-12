<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\UnionType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MetaTagUnion",
 * )
 */
class MetaTagUnion extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new UnionType([
      'name' => $this->getPluginId(),
      'types' => fn() => [
        static::type('MetaTagLink'),
        static::type('MetaTagValue'),
        static::type('MetaTagProperty'),
      ],
    ]);

    return $types;
  }

}
