<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "DateRange"
 * )
 */
class DateRangeType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('datetime_range')) {
      return [];
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A Date range has a start and an end.'),
      'fields' => fn() => [
        'start' => [
          'type' => static::type('DateTime'),
          'description' => (string) $this->t('The start of the date range.'),
        ],
        'end' => [
          'type' => static::type('DateTime'),
          'description' => (string) $this->t('The end of the date range.'),
        ],
      ],
    ]);

    return $types;
  }

}
