<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "File",
 * )
 */
class FileType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A file object to represent an managed file.'),
      'fields' => fn() => [
        'name' => Type::string(),
        'url'  => Type::nonNull(Type::string()),
        'size' => Type::nonNull(Type::int()),
        'mime' => Type::string(),
        'description' => Type::string(),
      ],
    ]);

    return $types;
  }

}
