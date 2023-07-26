<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_config_pages\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "svg_image_field",
 *   type_sdl = "SVG",
 * )
 */
class SvgItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {
    if (!$item->entity) {
      return NULL;
    }

    $access = $item->entity->access('view', NULL, TRUE);
    $metadata->addCacheableDependency($access);

    if (!$access) {
      return NULL;
    }

    return [
      'url'  => \Drupal::service('file_url_generator')->generateAbsoluteString($item->entity->getFileUri()),
      'name' => $item->entity->getFilename(),
      'size' => (int) $item->entity->getSize(),
      'mime' => $item->entity->getMimeType(),
      'description'  => $item->description ?: NULL,
    ];
  }

}
