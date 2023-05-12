<?php

namespace Drupal\image_styles_builder\Plugin\Derivative;

/**
 * Defines the class for image style.
 */
class ImageStyle {

  /**
   * The style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The style effects.
   *
   * @var \Drupal\image_styles_builder\Plugin\Derivative\ImageEffect[]
   */
  protected array $effects = [];

  /**
   * Constructs a new DerivativeStyle object.
   *
   * @param string $id
   *   The style ID.
   * @param array $effects
   *   The style effects.
   * @param string $suffix
   *   The suffix.
   */
  public function __construct(string $id, array $effects = [], string $suffix = '') {
    $this->id = $suffix ? $suffix . '_' . $id : $id;

    if (!empty($effects)) {
      // Populate value objects for effects.
      foreach ($effects as $id => $effect) {
        $width = $effect['width'] ?? NULL;
        $height = $effect['height'] ?? NULL;
        $data = $effect['data'] ?? [];

        $this->effects[$id] = new ImageEffect($effect['type'], $width, $height, $data);
      }
    }
  }

  /**
   * Gets the string representation of the derivative style.
   *
   * @return string
   *   The string representation of the derivative style.
   */
  public function __toString() {
    return $this->getId();
  }

  /**
   * Gets the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the style effects.
   *
   * @return array
   *   The style effects.
   */
  public function getEffects(): array {
    return $this->effects;
  }

}
