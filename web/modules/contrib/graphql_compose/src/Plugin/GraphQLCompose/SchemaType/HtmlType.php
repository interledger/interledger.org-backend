<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\CustomScalarType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Html",
 * )
 */
class HtmlType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new CustomScalarType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('
        An HTML string

        Content of this type is deemed safe by the server for raw output given the
        origin and the context of its usage. The HTML can still contain scripts or style
        tags where the creating user had permission to input these.
      '),
    ]);

    return $types;
  }

}
