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
        'value' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The raw text value.'),
        ],
        'processed' => [
          'type' => static::type('Html'),
          'description' => (string) $this->t('The processed text value.'),
        ],
        'format' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The text format used to process the text value.'),
        ],
        'summary' => [
          'type' => static::type('Html'),
          'description' => (string) $this->t('The processed text summary.'),
        ],
      ],
    ]);

    return $types;
  }

}
