<?php

namespace Drupal\image_styles_builder\Plugin\Derivative;

/**
 * Defines the class for image style effect.
 */
class ImageEffect {

  /**
   * The effect.
   *
   * @var string
   */
  protected $type;

  /**
   * The style width.
   *
   * @var float|null
   */
  protected $width;

  /**
   * The style height.
   *
   * @var float|null
   */
  protected $height;

  /**
   * The effect options/data.
   *
   * @var array|null
   */
  protected $data;

  /**
   * Constructs a new ImageEffect object.
   *
   * @param string $type
   *   The style effect type.
   * @param float|null $width
   *   The style effect width.
   * @param float|null $height
   *   The style effect height.
   * @param array|null $data
   *   The style effect data.
   */
  public function __construct(string $type, ?float $width, ?float $height, ?array $data = []) {
    $this->type = $type;
    $this->width = $width;
    $this->height = $height;
    $this->data = $data;
  }

  /**
   * Gets the string representation of the effect.
   *
   * @return string
   *   The string representation of the effect.
   */
  public function __toString() {
    $options = [];

    if ($this->getWidth() || $this->getHeight()) {
      $width = $this->getWidth() ?? 'auto';
      $height = $this->getHeight() ?? 'auto';
      $options[] = sprintf('(%s x %s)', $width, $height);
    }

    if (!empty($this->getData())) {
      foreach ($this->getData() as $key => $value) {
        $options[] = sprintf('(%s: %s)', $key, $value);
      }
    }

    return sprintf('%s %s', $this->getType(), implode(' ', $options));
  }

  /**
   * Gets the style effect type.
   *
   * @return string
   *   The style effect type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets the style height.
   *
   * @return float|null
   *   The style height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Gets the style width.
   *
   * @return float|null
   *   The style width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Gets the data.
   *
   * @return array|null
   *   The data.
   */
  public function getData() {
    return $this->data;
  }

}
