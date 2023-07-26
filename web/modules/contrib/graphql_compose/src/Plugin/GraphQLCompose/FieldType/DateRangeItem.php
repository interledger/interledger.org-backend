<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "daterange",
 *   type_sdl = "DateRange",
 * )
 */
class DateRangeItem extends DateTimeItem implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {

    $start_class = clone $item;
    $start_class->value = $item->value;

    $end_class = clone $item;
    $end_class->value = $item->end_value;

    $start = parent::resolveFieldItem($start_class, $context, $metadata);
    $end = parent::resolveFieldItem($end_class, $context, $metadata);

    return [
      'start' => $item->value ? $start : NULL,
      'end' => $item->end_value ? $end : NULL,
    ];
  }

}
