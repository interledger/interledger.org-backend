<?php

namespace Drupal\paragraphs_ee\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormState;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Controller for the Paragraphs off-canvas browser.
 */
class ParagraphsOffCanvasBrowser extends ControllerBase implements ParagraphsOffCanvasBrowserInterface {

  /**
   * The form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null
   */
  protected $formDisplay = NULL;

  /**
   * {@inheritdoc}
   */
  public function getTitle(string $entity_type, string $bundle, string $form_mode, string $field_name): string {
    $title_default = $this->t('Add Paragraph', [], ['context' => 'Paragraphs Editor Enhancements']);

    $component = $this->getComponent($entity_type, $bundle, $form_mode, $field_name);

    if (is_null($component)) {
      return $title_default;
    }

    if (!isset($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) || ($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas'] !== TRUE)) {
      return $title_default;
    }

    return $this->t('Add @widget_title', ['@widget_title' => $component['settings']['title']], ['context' => 'Paragraphs Editor Enhancements']);
  }

  /**
   * {@inheritdoc}
   */
  public function content(string $entity_type, string $bundle, string $form_mode, string $field_name): array {
    $build = [];

    $component = $this->getComponent($entity_type, $bundle, $form_mode, $field_name);

    if (is_null($component)) {
      return $build;
    }

    if (!isset($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) || ($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas'] !== TRUE)) {
      return $build;
    }

    // Load the paragraphs widget for the field.
    /** @var \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget $widget */
    $widget = $this->getWidget($entity_type, $bundle, $form_mode, $field_name);
    if (!($widget instanceof ParagraphsWidget) || ('modal' !== $widget->getSetting('add_mode'))) {
      return $build;
    }

    $form = [
      '#parents' => [],
    ];
    $form_state = new FormState();
    // Create an empty entity of type $entity_type and bundle $bundle.
    $entity = $this->entityTypeManager->getStorage($entity_type)->create([
      'type' => $bundle,
    ]);
    $form_state->set('entity', $entity);
    $form_state->set('paragraphs_ee-add_mode', 'off_canvas');
    $form_state->set('form_display', $this->getFormDisplay($entity_type, $bundle, $form_mode));
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $entity->get($field_name);
    $items->filterEmptyItems();
    $widget_form = $widget->form($items, $form, $form_state);

    $build['dialog'] = $widget_form['widget']['add_more'];
    $build['dialog']['#add'] = NULL;
    $build['dialog']['#add_mode'] = 'off_canvas';

    $build['#attached']['library'][] = 'paragraphs_ee/paragraphs_ee.categories';
    $build['#attached']['library'][] = 'paragraphs_ee/paragraphs_ee.off_canvas';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(string $entity_type, string $bundle, string $form_mode): ?EntityFormDisplayInterface {
    if (is_null($this->formDisplay)) {
      $this->formDisplay = $this->entityTypeManager()
        ->getStorage('entity_form_display')
        ->load($entity_type . '.' . $bundle . '.' . $form_mode);
    }

    return $this->formDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent(string $entity_type, string $bundle, string $form_mode, string $field_name): ?array {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null $form_display */
    $form_display = $this->getFormDisplay($entity_type, $bundle, $form_mode);
    if (is_null($form_display)) {
      return NULL;
    }

    $component = $form_display->getComponent($field_name);
    if (!$component || !isset($component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) || TRUE !== $component['third_party_settings']['paragraphs_ee']['paragraphs_ee']['dialog_off_canvas']) {
      return NULL;
    }

    return $component;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget(string $entity_type, string $bundle, string $form_mode, string $field_name): ?PluginSettingsInterface {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null $form_display */
    $form_display = $this->getFormDisplay($entity_type, $bundle, $form_mode);
    if (is_null($form_display)) {
      return NULL;
    }

    return $form_display->getRenderer($field_name);
  }

}
