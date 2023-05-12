<?php

namespace Drupal\image_styles_builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;

/**
 * Manages image styles flush.
 */
class ImageStyleFlusher {
  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ImageStyleManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->logger = $logger_factory->get('image_styles_builder');
  }

  /**
   * Flush an Image Style.
   *
   * @param \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle $image_style
   *   The image style to be generated.
   */
  public function flush(ImageStyle $image_style): void {
    $img_style = $this->imageStyleStorage->load($image_style->getId());
    if (!$img_style) {
      $this->logger->notice('The image style @machine_name does not exists.', ['@machine_name' => $image_style->getId()]);
      return;
    }

    $img_style->delete();
  }

}
