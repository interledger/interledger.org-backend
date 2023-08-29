<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_config_pages\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SVG",
 * )
 */
class SVGType extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A file object to represent an managed file.'),
      'fields' => fn () => [
        'url' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The URL of the svg.'),
        ],
        'title' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The title text of the image.'),
        ],
        'mime' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The mime type of the image.'),
        ],
      ],
    ]);

    return $types;
  }
}
