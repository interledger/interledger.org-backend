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
 *   id = "MetaTagLinkAttributes",
 * )
 */
class MetaTagLinkAttributes extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("A meta link element's attributes."),
      'fields' => fn() => [
        'rel' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The rel attribute of the link.'),
        ],
        'href' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The href attribute of the link.'),
        ],
      ],
    ]);

    return $types;
  }

}
