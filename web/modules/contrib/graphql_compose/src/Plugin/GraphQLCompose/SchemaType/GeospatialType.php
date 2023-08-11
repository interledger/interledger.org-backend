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
 *   id = "Geospatial",
 * )
 */
class GeospatialType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('geofield')) {
      return [];
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('This field stores geospatial information.'),
      'fields' => fn() => [
        'value' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The main value of the geofield. It can store any additional information related to the geospatial data.'),
        ],
        'geoType' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The type of geospatial data being stored, e.g., point, line, polygon, etc.'),
        ],
        'lat' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The latitude coordinate of the geospatial point.'),
        ],
        'lon' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The longitude coordinate of the geospatial point.'),
        ],
        'left' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The left boundary of a geospatial area, used for bounding box representation.'),
        ],
        'top' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The top boundary of a geospatial area, used for bounding box representation.'),
        ],
        'right' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The right boundary of a geospatial area, used for bounding box representation.'),
        ],
        'bottom' => [
          'type' => Type::float(),
          'description' => (string) $this->t('The bottom boundary of a geospatial area, used for bounding box representation.'),
        ],
        'geohash' => [
          'type' => Type::string(),
          'description' => (string) $this->t('A geohash representation of the geospatial point, which is a compact string representing the coordinates.'),
        ],
        'latlon' => [
          'type' => Type::string(),
          'description' => (string) $this->t('A combined string representation of latitude and longitude, useful for certain geospatial applications.'),
        ],
      ],
    ]);

    return $types;
  }

}
