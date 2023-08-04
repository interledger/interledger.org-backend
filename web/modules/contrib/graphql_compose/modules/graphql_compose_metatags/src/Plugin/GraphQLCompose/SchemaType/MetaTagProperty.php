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
 *   id = "MetaTagProperty",
 * )
 */
class MetaTagProperty extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A meta property element.'),
      'interfaces' => fn() => [
        static::type('MetaTag'),
      ],
      'fields' => fn() => [
        'tag' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The HTML tag for this meta element.'),
        ],
        'attributes' => [
          'type' => Type::nonNull(static::type('MetaTagPropertyAttributes')),
          'description' => (string) $this->t('The meta tag element attributes.'),
        ],
      ],
    ]);

    return $types;
  }

}
