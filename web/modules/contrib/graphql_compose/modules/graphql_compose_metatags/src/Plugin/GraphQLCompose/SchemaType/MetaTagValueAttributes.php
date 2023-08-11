<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQLCompose\SchemaType;

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
      'description' => (string) $this->t("A meta content element's attributes."),
      'fields' => fn() => [
        'name' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The name attribute of the meta tag.'),
        ],
        'content' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The content attribute of the meta tag.'),
        ],
      ],
    ]);

    return $types;
  }

}
