<?php

namespace Drupal\paragraphs_ee;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Paragraphs category entities.
 */
interface ParagraphsCategoryInterface extends ConfigEntityInterface {

  /**
   * Get the description of the Paragraphs category.
   *
   * @return string
   *   The category description.
   */
  public function getDescription();

  /**
   * Get the weight of the Paragraphs category.
   *
   * @return int
   *   The category weight.
   */
  public function getWeight();

}
