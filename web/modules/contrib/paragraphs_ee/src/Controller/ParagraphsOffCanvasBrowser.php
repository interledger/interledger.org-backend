<?php

namespace Drupal\paragraphs_ee\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\Attribute;

/**
 * Controller for the Paragraphs off-canvas browser.
 */
class ParagraphsOffCanvasBrowser extends ControllerBase {

  /**
   * Generate the title for the off-canvas browser page.
   */
  public function getTitle($entity_type, $bundle, $form_mode, $field_name) {
    $title_default = $this->t('Add Paragraph', [], ['context' => 'Paragraphs Editor Enhancements']);

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_mode);
    if (!$form_display) {
      return $title_default;
    }

    $component = $form_display->getComponent($field_name);
    if (!$component || !isset($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) || TRUE !== $component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) {
      return $title_default;
    }

    return $this->t('Add @widget_title', ['@widget_title' => $component['settings']['title']], ['context' => 'Paragraphs Editor Enhancements']);
  }

  /**
   * Build render array for the off-canvas browser page.
   */
  public function content($entity_type, $bundle, $form_mode, $field_name) {
    $build = [];

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_mode);
    if (!$form_display) {
      return $build;
    }

    $component = $form_display->getComponent($field_name);
    if (!$component || !isset($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) || TRUE !== $component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) {
      return $build;
    }

    $build['dialog'] = [
      '#theme' => 'paragraphs_add_dialog__categorized',
      '#add' => NULL,
      '#dialog_attributes' => new Attribute([
        'role' => 'dialog',
        'aria-modal' => 'false',
        'aria-label' => $this->t('Add @widget_title', ['@widget_title' => $component['settings']['title']], ['context' => 'Paragraphs Editor Enhancements']),
        'data-field-name' => $field_name,
        'data-widget-title' => $component['settings']['title'],
        'data-widget-title-plural' => $component['settings']['title_plural'],
        'class' => [
          'paragraphs-add-dialog',
          'paragraphs-add-dialog--categorized',
        ],
      ]),
      '#groups' => [],
      '#add_mode' => 'off_canvas',
    ];

    $build['#attached']['library'][] = 'paragraphs_ee/paragraphs_ee.off_canvas';

    return $build;
  }

}
