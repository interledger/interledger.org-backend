<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layouts\Plugin\GraphQL\DataProducer;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Load drupal layout definition property.
 *
 * @DataProducer(
 *   id = "layout_definition_property",
 *   name = @Translation("Layout Definition property"),
 *   description = @Translation(" Layout Definition property by key."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Value of property")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("any",
 *       label = @Translation("Layout Definition")
 *     ),
 *     "path" = @ContextDefinition("any",
 *       label = @Translation("Property")
 *     ),
 *   }
 * )
 */
class LayoutDefinitionProperty extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolve the layout definition.
   *
   * @param \Drupal\Core\Layout\LayoutDefinition $layout
   *   The layout definition.
   * @param string $path
   *   The property path.
   *
   * @return mixed
   *   The value of the property.
   */
  public function resolve(LayoutDefinition $layout, string $path): mixed {
    return ($path === 'regions') ? $layout->getRegionNames() : $layout->get($path);
  }

}
