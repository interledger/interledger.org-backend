<?php

namespace Drupal\paragraphs_ee\Form;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the add and edit forms of Paragraphs category entities.
 */
interface ParagraphsCategoryFormInterface extends EntityFormInterface {

  /**
   * Constructs an ParagraphsCategoryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager);

  /**
   * Helper function to check whether a Paragraphs category entity exists.
   *
   * @param string $id
   *   Identifier of paragraph category.
   *
   * @return bool
   *   <code>TRUE</code> if a category with the given ID exists,
   *   <code>FALSE</code> otherwise.
   */
  public function exist(string $id): bool;

  /**
   * Custom validation handler for machine name element.
   *
   * @param array<mixed> $element
   *   Form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function validateMachineName(array $element, FormStateInterface $form_state): void;

}
