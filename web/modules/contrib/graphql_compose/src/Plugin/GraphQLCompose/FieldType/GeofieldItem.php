<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "geofield",
 *   type_sdl = "Geospatial"
 * )
 */
class GeofieldItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {
    return [
      'value' => $item->value ?: NULL,
      'geoType' => $item->geo_type ?: NULL,
      'lat' => $item->lat ?: NULL,
      'lon' => $item->lon ?: NULL,
      'left' => $item->left ?: NULL,
      'top' => $item->top ?: NULL,
      'right' => $item->right ?: NULL,
      'bottom' => $item->bottom ?: NULL,
      'geohash' => $item->geohash ?: NULL,
      'latlon' => $item->latlon ?: NULL,
    ];
  }

}
