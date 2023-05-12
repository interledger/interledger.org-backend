<?php

namespace Drupal\image_styles_builder\Plugin\Derivative;

/**
 * Defines the interface for derivatives.
 */
interface DerivativeInterface {

  /**
   * Gets the derivative ID.
   *
   * @return string
   *   The derivative ID.
   */
  public function getId();

  /**
   * Gets the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

  /**
   * Gets the derivative suffix.
   *
   * @return string
   *   The derivative suffix.
   */
  public function getSuffix();

  /**
   * Gets the derivative styles.
   *
   * @return \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle[]
   *   The styles, keyed by style ID.
   */
  public function getStyles();

  /**
   * Gets a derivative style with the given ID.
   *
   * @param string $id
   *   The style ID.
   *
   * @return \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle|null
   *   The requested style, or NULL if not found.
   */
  public function getStyle($id);

}
