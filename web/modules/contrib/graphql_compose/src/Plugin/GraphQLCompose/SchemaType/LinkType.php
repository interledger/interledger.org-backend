<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Link",
 * )
 */
class LinkType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A link.'),
      'fields' => fn() => [
        'title' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The title of the link.'),
        ],
        'url' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The URL of the link.'),
        ],
        'internal' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether the link is internal to this website.'),
        ],
      ],
    ]);

    return $types;
  }

}
