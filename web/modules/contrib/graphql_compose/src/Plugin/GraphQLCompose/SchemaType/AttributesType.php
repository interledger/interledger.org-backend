<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\CustomScalarType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Attributes",
 * )
 */
class AttributesType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new CustomScalarType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Untyped attribute data. Eg configuration, settings, attributes.'),
    ]);

    return $types;
  }

}
