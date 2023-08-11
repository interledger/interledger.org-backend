<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run field item resolution on graphql compose field type plugin.
 *
 * @DataProducer(
 *   id = "field_type_plugin",
 *   name = @Translation("Field plugin resolver"),
 *   description = @Translation("Returns plugin field item resolution."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Field plugin item result")
 *   ),
 *   consumes = {
 *     "plugin" = @ContextDefinition("any",
 *       label = @Translation("Field plugin instance")
 *     ),
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Field values")
 *     )
 *   }
 * )
 */
class FieldProducer extends DataProducerPluginBase implements FieldProducerItemsInterface, FieldProducerItemInterface, ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->renderer = $container->get('renderer');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItems(FieldItemListInterface $field, array $context, RefinableCacheableDependencyInterface $metadata): array {

    $plugin = $context['plugin'];

    if ($plugin instanceof FieldProducerItemsInterface) {
      return $plugin->resolveFieldItems($field, $context, $metadata);
    }

    $results = [];
    foreach ($field as $item) {
      $results[] = $this->resolveFieldItem($item, $context, $metadata);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, array $context, RefinableCacheableDependencyInterface $metadata) {
    $plugin = $context['plugin'];

    if ($plugin instanceof FieldProducerItemInterface) {
      return $plugin->resolveFieldItem($item, $context, $metadata);
    }

    return $item->{$plugin->producerProperty ?? 'value'};
  }

  /**
   * Resolve producer field items.
   *
   * @param mixed $consumes
   *   Consumption options passed to the field.
   *
   * @return mixed
   *   Results from resolution. Array for multiple.
   */
  public function resolve(...$consumes) {
    $context = $this->getContextValues();
    $value = $context['value'] ?? NULL;
    $plugin = $context['plugin'] ?? NULL;

    // How did you get here?
    if (!$value || !$value instanceof FieldItemListInterface) {
      return NULL;
    }

    // Pull metadata out of the child class implementation.
    $metadata = array_filter($consumes, fn ($item) => $item instanceof CacheableDependencyInterface);
    $metadata = reset($metadata);

    // Process the field.
    $results = $this->resolveFieldItems($value, $context, $metadata);

    $this->moduleHandler->invokeAll('graphql_compose_field_results_alter', [
      &$results,
      $context,
      $metadata,
    ]);

    if (empty($results)) {
      return NULL;
    }

    return $plugin->isMultiple() ? $results : reset($results);
  }

}
