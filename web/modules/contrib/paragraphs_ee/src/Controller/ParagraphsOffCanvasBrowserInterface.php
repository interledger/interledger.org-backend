<?php

namespace Drupal\paragraphs_ee\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Field\PluginSettingsInterface;

/**
 * Interface for the Paragraphs off-canvas browser controller.
 */
interface ParagraphsOffCanvasBrowserInterface extends ContainerInjectionInterface {

  /**
   * Generate the title for the off-canvas browser page.
   *
   * @param string $entity_type
   *   Type of entity the off-canvas browser is displayed on.
   * @param string $bundle
   *   Bundle of entity the off-canvas browser is displayed on.
   * @param string $form_mode
   *   Form mode to use.
   * @param string $field_name
   *   Name of the paragraph reference field.
   *
   * @return string
   *   Title of the off-canvas browser.
   */
  public function getTitle(string $entity_type, string $bundle, string $form_mode, string $field_name): string;

  /**
   * Build render array for the off-canvas browser page.
   *
   * @param string $entity_type
   *   Type of entity the off-canvas browser is displayed on.
   * @param string $bundle
   *   Bundle of entity the off-canvas browser is displayed on.
   * @param string $form_mode
   *   Form mode to use.
   * @param string $field_name
   *   Name of the paragraph reference field.
   *
   * @return array<array>
   *   Render array of the off-canvas content.
   */
  public function content(string $entity_type, string $bundle, string $form_mode, string $field_name): array;

  /**
   * Get the form display for a given form mode..
   *
   * @param string $entity_type
   *   Type of entity the off-canvas browser is displayed on.
   * @param string $bundle
   *   Bundle of entity the off-canvas browser is displayed on.
   * @param string $form_mode
   *   Form mode to use.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null
   *   The form display or NULL if it does not exist.
   */
  public function getFormDisplay(string $entity_type, string $bundle, string $form_mode): ?EntityFormDisplayInterface;

  /**
   * Get the form component for the given parameters.
   *
   * @param string $entity_type
   *   Type of entity the off-canvas browser is displayed on.
   * @param string $bundle
   *   Bundle of entity the off-canvas browser is displayed on.
   * @param string $form_mode
   *   Form mode to use.
   * @param string $field_name
   *   Name of the paragraph reference field.
   *
   * @return array<string>|null
   *   The form component or NULL, if the field does not exist.
   */
  public function getComponent(string $entity_type, string $bundle, string $form_mode, string $field_name): ?array;

  /**
   * Get the form widget for the given parameters.
   *
   * @param string $entity_type
   *   Type of entity the off-canvas browser is displayed on.
   * @param string $bundle
   *   Bundle of entity the off-canvas browser is displayed on.
   * @param string $form_mode
   *   Form mode to use.
   * @param string $field_name
   *   Name of the paragraph reference field.
   *
   * @return \Drupal\Core\Field\PluginSettingsInterface|null
   *   A widget plugin or NULL, if the field does not exist.
   */
  public function getWidget(string $entity_type, string $bundle, string $form_mode, string $field_name): ?PluginSettingsInterface;

}
