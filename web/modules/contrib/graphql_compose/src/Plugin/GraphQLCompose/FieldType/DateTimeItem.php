<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "datetime",
 *   type_sdl = "DateTime",
 * )
 */
class DateTimeItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    $value = $item->value;

    $date_time = is_numeric($value)
      ? DrupalDateTime::createFromTimestamp($value)
      : new DrupalDateTime($value, new \DateTimeZone('UTC'));

    return [
      'timestamp' => $date_time->getTimestamp(),
      'timezone' => $date_time->getTimezone()->getName(),
      'offset' => $date_time->format('P'),
      'time' => $date_time->format(\DateTime::RFC3339),
    ];
  }

}
