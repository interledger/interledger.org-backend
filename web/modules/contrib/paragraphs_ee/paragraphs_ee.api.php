<?php

/**
 * @file
 * Hooks for the paragraphs_ee module.
 */

use Drupal\Core\Access\AccessResult;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Set access result for modifications made to widgets by paragraphs_ee.
 *
 * @param array $elements
 *   The field widget form elements as constructed by
 *   \Drupal\Core\Field\WidgetBase::formMultipleElements().
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - default: A boolean indicating whether the form is being shown as a dummy
 *     form to set default values.
 */
function hook_paragraphs_ee_widget_access(array $elements, FormStateInterface $form_state, array $context) {
  /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $items */
  $items = $context['items'];
  if (empty($items)) {
    return AccessResult::forbidden('No items available in widget.');
  }
  return AccessResult::neutral();
}

/**
 * @} End of "addtogroup hooks".
 */
