<?php

namespace Drupal\focal_point\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * Crops image while keeping its focal point as close to centered as possible.
 *
 * @ImageEffect(
 *   id = "focal_point_crop_ratio",
 *   label = @Translation("Focal Point Crop by Ratio"),
 *   description = @Translation("Crops image while keeping its focal point as close to centered as possible.")
 * )
 */
class FocalPointCropRatioImageEffect extends FocalPointCropImageEffect {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'crop_type' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['aspect_ratio'] = [
      '#title' => $this->t('Aspect Ratio'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['aspect_ratio'],
      '#attributes' => ['placeholder' => 'W:H'],
      '#description' => $this->t('Set an aspect ratio <b>eg: 16:9</b> or leave this empty for arbitrary aspect ratio'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['aspect_ratio'] = $form_state->getValue('aspect_ratio');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->configuration['aspect_ratio'],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function applyEffect(ImageInterface $image) {
    $this->calcAndSetNewSizes($image->getWidth(), $image->getHeight());
    return parent::applyEffect($image);
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    $this->calcAndSetNewSizes($dimensions['width'], $dimensions['height']);
    parent::transformDimensions($dimensions, $uri);
  }

  /**
   * Calculates and sets the new width and height based on the target ratio.
   *
   * @param int $source_width
   *   Width of the original image.
   * @param int $source_height
   *   Height of the original image.
   */
  protected function calcAndSetNewSizes($source_width, $source_height) {
    $source_aspect_ratio = $source_width / $source_height;

    $target_aspect_ratio = $this->configuration['aspect_ratio'];
    [$target_aspect_ratio_width, $target_aspect_ratio_height] = explode(':', $target_aspect_ratio);
    $target_aspect_ratio_percent = $target_aspect_ratio_width / $target_aspect_ratio_height;

    $target_height = $source_height;
    $target_width = $source_width;

    // Source is wider than target in proportion.
    if ($source_aspect_ratio > $target_aspect_ratio_percent) {
      if (($source_height >= $source_width) && ($source_height >= ($source_width / $target_aspect_ratio_percent))) {
        $target_height = $source_width / $target_aspect_ratio_percent;
      }
      else {
        $target_width = $source_height * $target_aspect_ratio_percent;
      }
    }
    // Source is higher than target in proportion.
    elseif ($source_aspect_ratio < $target_aspect_ratio_percent) {
      if (($source_height >= $source_width) && ($source_width >= ($source_height * $target_aspect_ratio_percent))) {
        $target_width = $source_height * $target_aspect_ratio_percent;
      }
      else {
        $target_height = $source_width / $target_aspect_ratio_percent;
      }
    }

    $this->configuration['width'] = $target_width;
    $this->configuration['height'] = $target_height;
  }

}
