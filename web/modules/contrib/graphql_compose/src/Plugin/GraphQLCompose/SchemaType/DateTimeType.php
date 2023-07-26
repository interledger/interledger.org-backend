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
 *   id = "DateTime"
 * )
 */
class DateTimeType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A DateTime object.'),
      'fields' => fn() => [
        'timestamp' => Type::nonNull(static::type('Timestamp')),
        'timezone' => Type::nonNull(static::type('TimeZone')),
        'offset' => Type::nonNull(static::type('UtcOffset')),
        'time' => Type::nonNull(static::type('Time')),
      ],
    ]);

    return $types;
  }

}
