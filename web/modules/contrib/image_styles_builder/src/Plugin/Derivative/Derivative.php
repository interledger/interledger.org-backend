<?php

namespace Drupal\image_styles_builder\Plugin\Derivative;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines the class for derivative.
 */
class Derivative extends PluginBase implements DerivativeInterface {

  /**
   * The initialized styles.
   *
   * @var \Drupal\image_styles_builder\Plugin\Derivative\ImageStyle[]
   */
  protected $styles = [];

  /**
   * Constructs a new Derivative object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The derivative plugin_id.
   * @param mixed $plugin_definition
   *   The derivative plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Populate value objects for styles.
    foreach ($plugin_definition['styles'] as $id => $style_definition) {
      $this->styles[$id] = new ImageStyle($id, $style_definition['effects'], $plugin_definition['suffix']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSuffix() {
    return $this->pluginDefinition['suffix'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles() {
    return $this->styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle($id) {
    return $this->styles[$id] ?? NULL;
  }

}
