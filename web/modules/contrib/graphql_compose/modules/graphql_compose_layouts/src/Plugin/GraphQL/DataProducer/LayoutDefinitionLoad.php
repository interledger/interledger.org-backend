<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layouts\Plugin\GraphQL\DataProducer;

use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load drupal layout definition.
 *
 * @DataProducer(
 *   id = "layout_definition_load",
 *   name = @Translation("Layout Definition"),
 *   description = @Translation("Load Layout Definition by ID."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Layout Definition")
 *   ),
 *   consumes = {
 *     "id" = @ContextDefinition("any",
 *       label = @Translation("Layout ID")
 *     ),
 *   }
 * )
 */
class LayoutDefinitionLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.core.layout'),
    );
  }

  /**
   * LayoutDefinitionLoad constructor.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    protected LayoutPluginManager $layoutManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * Resolve the layout definition.
   */
  public function resolve(?string $id): ?LayoutDefinition {
    return $this->layoutManager->getDefinition($id);
  }

}
