<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges;

/**
 * Utility trait to filter enabled bundles.
 */
trait EnabledBundlesTrait {

  /**
   * Get enabled bundle instances for resolution.
   *
   * @return \Drupal\graphql_compose\Wrapper\EntityTypeWrapper[]
   *   Bundles enabled.
   */
  protected function getEnabledBundlePlugins(): array {

    $enabled = &drupal_static(__TRAIT__ . '::' . __FUNCTION__);
    if (isset($enabled)) {
      return $enabled;
    }

    $enabled = [];

    /** @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface[] $entity_type_plugins */
    $entity_type_plugins = \Drupal::service('graphql_compose.entity_type_manager')->getPluginInstances();

    foreach ($entity_type_plugins as $entity_type_plugin) {
      foreach ($entity_type_plugin->getBundles() as $bundle) {
        if ($bundle->getSetting('edges_enabled')) {
          $enabled[] = $bundle;
        }
      }
    }

    return $enabled;
  }

}
