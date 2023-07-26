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
 *   id = "TextSummary",
 * )
 */
class TextSummaryType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A processed text format with summary defined by the CMS.'),
      'fields' => fn() => [
        'format' => Type::string(),
        'value' => Type::string(),
        'processed' => static::type('Html'),
        'summary' => static::type('Html'),
      ],
    ]);

    return $types;
  }

}
