<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_metatags_schema\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MetaTagValueAttributes",
 * )
 */
class MetaTagValueAttributes extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'fields' => fn() => [
        'name' => Type::string(),
        'content' => Type::string(),
      ],
    ]);

    return $types;
  }

}
