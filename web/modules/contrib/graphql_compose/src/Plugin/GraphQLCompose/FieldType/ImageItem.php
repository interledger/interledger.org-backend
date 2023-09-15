<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "image",
 *   type_sdl = "Image",
 * )
 */
class ImageItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, ContainerFactoryPluginInterface {

  use FieldProducerTrait;

  /**
   * File URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Drupal image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected ImageFactory $imageFactory;

  /**
   * Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->imageFactory = $container->get('image.factory');
    $instance->renderer = $container->get('renderer');

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

    if (!$access->isAllowed()) {
      return NULL;
    }

    $context = new RenderContext();
    $url = $this->renderer->executeInRenderContext($context, function () use ($item) {
      return $this->fileUrlGenerator->generateAbsoluteString($item->entity->getFileUri());
    });

    if (!$context->isEmpty()) {
      $metadata->addCacheableDependency($context->pop());
    }

    $metadata->addCacheableDependency($item->entity);

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
      'url' => $url,
      'width' => $width ?: 0,
      'height' => $height ?: 0,
      'alt' => $item->alt ?: NULL,
      'title' => $item->title ?: NULL,
      'mime' => $item->entity->getMimeType(),
    ];
  }

}
