<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Derivatives of entity.
 */
class QueryEntityTypePluginTypeDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   *
   * Return a deriver with a pattern of PLUGIN:ENTITYTYPE:BUNDLE.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    /** @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeInterface[] $entity_type_plugins */
    $entity_type_plugins = \Drupal::service('graphql_compose.entity_type_manager')->getPluginInstances();

    foreach ($entity_type_plugins as $entity_type_id => $entity_type_plugin) {
      foreach (array_keys($entity_type_plugin->getBundles()) as $bundle_id) {
        $this->derivatives[$entity_type_id . ':' . $bundle_id] = $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
