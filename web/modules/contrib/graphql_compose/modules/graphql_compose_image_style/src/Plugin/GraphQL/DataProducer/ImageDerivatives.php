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
   * Constructs a ImageDerivatives object.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\graphql\Plugin\DataProducerPluginManager $dataProducerPluginManager
   *   Data producer manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected DataProducerPluginManager $dataProducerPluginManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.graphql.data_producer'),
    );
  }

  /**
   * Finds the enum value(s) and loads the image_derivative data producer(s).
   *
   * @param \Drupal\file\FileInterface|null $entity
   *   The file entity.
   * @param string|array $value
   *   The image style name.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return array|null
   *   The image derivative loaded by enum name.
   */
  public function resolve(?FileInterface $entity = NULL, string|array $value, RefinableCacheableDependencyInterface $metadata): ?array {
    // Return if we don't have an entity.
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
