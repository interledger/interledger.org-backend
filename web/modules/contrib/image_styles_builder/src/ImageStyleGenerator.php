<?php

namespace Drupal\image_styles_builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\image\ImageEffectManager;
use Drupal\image\ImageStyleInterface;
use Drupal\image_styles_builder\Plugin\Derivative\ImageStyle;

/**
 * Manages image styles generation.
 */
class ImageStyleGenerator {
  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $imageEffectManager;

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
   * @param \Drupal\image\ImageEffectManager $image_effect_manager
   *   The image effect manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ImageEffectManager $image_effect_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->imageEffectManager = $image_effect_manager;
    $this->logger = $logger_factory->get('image_styles_builder');
  }

  /**
   * Generate an Image Style.
   *
   * @param \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle $image_style
   *   The image style to be generated.
   *
   * @return \Drupal\image\ImageStyleInterface|null
   *   The generated image style with all effects.
   */
  public function generate(ImageStyle $image_style): ?ImageStyleInterface {
    if ($this->imageStyleStorage->load($image_style->getId())) {
      $this->logger->notice('The image style @machine_name already exists.', ['@machine_name' => $image_style->getId()]);

      return NULL;
    }

    // Create image style.
    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = $this->imageStyleStorage->create([
      'name' => $image_style->getId(),
      'label' => $image_style->getId(),
    ]);

    foreach ($image_style->getEffects() as $weight => $effect) {
      // Init effect settings.
      $data = $effect->getData() ?? NULL;

      if ($effect->getWidth()) {
        $data['width'] = $effect->getWidth();
      }

      if ($effect->getHeight()) {
        $data['height'] = $effect->getHeight();
      }

      // Create the effect configuration.
      $configuration = [
        'uuid' => NULL,
        'id' => $effect->getType(),
        'data' => $data,
        'weight' => $weight,
        'name' => $image_style->getId(),
      ];

      $image_effect = $this->imageEffectManager->createInstance($configuration['id'], $configuration);
      $style->addImageEffect($image_effect->getConfiguration());
    }

    // Save the image styles with all effects.
    $style->save();

    return $style;
  }

}
