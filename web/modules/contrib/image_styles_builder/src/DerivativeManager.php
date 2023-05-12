<?php

namespace Drupal\image_styles_builder;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\image_styles_builder\Plugin\Derivative\Derivative;

/**
 * Manages discovery and instantiation of derivative plugins.
 *
 * @see plugin_api
 */
class DerivativeManager extends DefaultPluginManager implements PluginManagerInterface {

  /**
   * A cache of loaded derivative, keyed by derivative ID.
   *
   * @var \Drupal\image_styles_builder\Plugin\Derivative\DerivativeInterface[]
   */
  protected $plugins;

  /**
   * Default values for each derivatives plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'suffix' => '',
    'styles' => [],
  ];

  /**
   * Constructs a new ImageStylesManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'image_style_builder', ['image_style_builder']);
    $this->alterInfo('image_style_builder_derivatives');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!empty($this->plugins[$plugin_id])) {
      return $this->plugins[$plugin_id];
    }

    $plugin_definition = $this->getDefinition($plugin_id);
    $this->plugins[$plugin_id] = new Derivative($configuration, $plugin_id, $plugin_definition);

    return $this->plugins[$plugin_id];
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress MissingParamType
   */
  public function processDefinition(&$definition, $plugin_id): void {
    parent::processDefinition($definition, $plugin_id);

    $definition['id'] = $plugin_id;

    foreach (['label', 'suffix', 'styles'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The derivative %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    foreach ($definition['styles'] as $state_id => $state_definition) {
      if (empty($state_definition['effects'])) {
        throw new PluginException(sprintf('The derivative style %s must define the effect property.', $state_id));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress RedundantPropertyInitializationCheck
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('image_styles_builder_derivatives', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }

    return $this->discovery;
  }

}
