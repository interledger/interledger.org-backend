<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes;

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
  protected function getEnabledBundlePlugins() {
    $result = [];

    $entity_type_plugins = \Drupal::service('graphql_compose.entity_type_manager')->getPluginInstances();

    foreach ($entity_type_plugins as $entity_type_plugin) {
      foreach ($entity_type_plugin->getBundles() as $bundle) {
        if ($bundle->getSetting('routes_enabled')) {
          $result[] = $bundle;
        }
      }
    }

    return $result;
  }

}
