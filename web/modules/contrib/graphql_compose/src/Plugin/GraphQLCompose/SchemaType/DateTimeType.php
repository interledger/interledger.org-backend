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
        'timestamp' => [
          'type' => Type::nonNull(static::type('Timestamp')),
          'description' => (string) $this->t('Type represents date and time as number of milliseconds from start of the UNIX epoch.'),
        ],
        'timezone' => [
          'type' => Type::nonNull(static::type('TimeZone')),
          'description' => (string) $this->t('A field whose value exists in the standard IANA Time Zone Database.'),
        ],
        'offset' => [
          'type' => Type::nonNull(static::type('UtcOffset')),
          'description' => (string) $this->t('A string that will have a value of format ±hh:mm'),
        ],
        'time' => [
          'type' => Type::nonNull(static::type('Time')),
          'description' => (string) $this->t('RFC 3339 compliant time string.'),
        ],
      ],
    ]);

    return $types;
  }

}
