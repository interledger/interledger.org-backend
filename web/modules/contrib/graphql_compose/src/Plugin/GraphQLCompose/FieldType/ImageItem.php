<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "image",
 *   type_sdl = "Image",
 * )
 */
class ImageItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, ContainerFactoryPluginInterface {

  use FieldProducerTrait;

  /**
   * Drupal image factory.
   */
  protected ImageFactory $imageFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $instance = parent::create(
      $container,
      $configuration,
      $pluginId,
      $pluginDefinition,
    );

    $instance->imageFactory = $container->get('image.factory');

    return $instance;
  }

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

    $width = $item->width ?? NULL;
    $height = $item->height ?? NULL;

    if (is_null($width) || is_null($height)) {
      $image = $this->imageFactory->get($item->entity->getFileUri());
      if ($image->isValid()) {
        $width = $image->getWidth();
        $height = $image->getHeight();
      }
    }

    return [
      'url'    => \Drupal::service('file_url_generator')->generateAbsoluteString($item->entity->getFileUri()),
      'width'  => $width ?: 0,
      'height' => $height ?: 0,
      'alt'    => $item->alt ?: NULL,
      'title'  => $item->title ?: NULL,
      'mime'   => $item->entity->getMimeType(),
    ];
  }

}
