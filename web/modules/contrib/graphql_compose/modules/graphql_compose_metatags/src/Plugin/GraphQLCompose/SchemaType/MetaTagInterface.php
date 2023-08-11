<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "MetaTagInterface"
 * )
 */
class MetaTagInterface extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("This entity has meta tags enabled."),
      'fields' => fn() => [
        'metatag' => [
          'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('MetaTagUnion')))),
          'description' => (string) $this->t('The computed meta tags for the entity.'),
        ],
      ],
    ]);

    return $types;
  }

}
