<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_image_style\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function Symfony\Component\String\u;

/**
 * Get enum value.
 *
 * @DataProducer(
 *   id = "image_derivatives",
 *   name = @Translation("Load multiple image derivatives"),
 *   description = @Translation("Extension of image_derivative"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Image derivative properties")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = FALSE
 *     ),
 *     "styles" = @ContextDefinition("any",
 *       label = @Translation("Image styles")
 *     )
 *   }
 * )
 */
class ImageDerivatives extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Field producer constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\graphql\Plugin\DataProducerPluginManager $dataProducerPluginManager
   *   Data producer manager.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    protected DataProducerPluginManager $dataProducerPluginManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('plugin.manager.graphql.data_producer'),
    );
  }

  /**
   * Finds the requested enum value.
   */
  public function resolve(FileInterface $entity = NULL, string|array $value, RefinableCacheableDependencyInterface $metadata): ?array {
    // Return if we dont have an entity.
    if (!$entity) {
      return NULL;
    }

    /** @var \Drupal\graphql\Plugin\GraphQL\DataProducer\Entity\Fields\Image\ImageDerivative $plugin */
    $plugin = $this->dataProducerPluginManager->createInstance('image_derivative');

    $results = [];
    $styles = is_array($value) ? $value : [$value];

    foreach ($styles as $style) {
      $resolved = $plugin->resolve($entity, $style, $metadata);
      if ($resolved) {
        $resolved['name'] = u($style)->snake()->upper()->toString();
        $results[] = $resolved;
      }
    }

    return $results ?: NULL;
  }

}
