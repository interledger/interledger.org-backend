<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

/**
 * Extension for field plugin to enable unions.
 */
trait FieldUnionTrait
{

  /**
   * Calculated unions for field.
   *
   * @var array
   */
  protected array $unions;

  /**
   * {@inheritdoc}
   *
   * Replace SDL with union when multiple unions are returned.
   * If single target bundle, return just that target's type_sdl.
   */
  public function getTypeSdl(): string {
    // Enable overriding.
    if (!empty($this->pluginDefinition['type_sdl'])) {
      return $this->pluginDefinition['type_sdl'];
    }

    // No field, unable to get target type.
    if (!$field_definition = $this->getFieldDefinition()) {
      return 'UnsupportedType';
    }

    // Unknown target type.
    if (!$target_type_id = $field_definition->getSetting('target_type')) {
      return 'UnsupportedType';
    }

    // Entity type not defined.
    if (!$entity_type = $this->entityTypeManager->getDefinition($target_type_id, FALSE)) {
      return 'UnsupportedType';
    }

    // Entity type plugin not defined.
    if (!$plugin_instance = $this->gqlEntityTypeManager->getPluginInstance($target_type_id)) {
      return 'UnsupportedType';
    }

    // No enabled bundles.
    if (empty($plugin_instance->getBundles())) {
      return 'UnsupportedType';
    }


    $settings = $field_definition->getSettings();
    $target_bundles = $settings["handler_settings"]["target_bundles"];
    if ($target_bundles && count($target_bundles) > 1) {
      return $this->getUnionTypeSdl();
    }

    if ($target_bundles && $bundle = array_key_first($target_bundles)) {
      $getBundle = $plugin_instance->getBundle($bundle);

      if (!$getBundle) {
        return $plugin_instance->getUnionTypeSdl();
      }

      return $plugin_instance->getBundle($bundle)->getTypeSdl();
    }

    if (!$entity_type->getBundleEntityType()) {
      return $plugin_instance->getTypeSdl();
    }


    return $plugin_instance->getUnionTypeSdl();
  }

}
