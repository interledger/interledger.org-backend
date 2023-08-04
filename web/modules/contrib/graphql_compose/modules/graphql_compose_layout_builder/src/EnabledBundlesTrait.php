<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\layout_builder\LayoutBuilderEnabledInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock;

use function Symfony\Component\String\u;

/**
 * Utility trait to filter enabled bundles.
 */
trait EnabledBundlesTrait {

  /**
   * Entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager ?? \Drupal::service('entity_type.manager');
  }

  /**
   * Entity display repository service.
   *
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity display repository service.
   */
  protected function entityDisplayRepository(): EntityDisplayRepositoryInterface {
    return $this->entityDisplayRepository ?? \Drupal::service('entity_display.repository');
  }

  /**
   * GraphQL Compose entity type manager.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager
   *   The GraphQL Compose entity type manager.
   */
  protected function gqlEntityTypeManager(): GraphQLComposeEntityTypeManager {
    return $this->gqlEntityTypeManager ?? \Drupal::service('graphql_compose.entity_type_manager');
  }

  /**
   * GraphQL Compose field type manager.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager
   *   The GraphQL Compose field type manager.
   */
  protected function gqlFieldTypeManager(): GraphQLComposeFieldTypeManager {
    return $this->gqlFieldTypeManager ?? \Drupal::service('graphql_compose.field_type_manager');
  }

  /**
   * Drupal Config Factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected function configFactory() {
    return $this->configFactory ?? \Drupal::service('config.factory');
  }

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
    $entity_type_plugins = $this->gqlEntityTypeManager()->getPluginInstances();

    foreach ($entity_type_plugins as $entity_type_plugin) {
      foreach ($entity_type_plugin->getBundles() as $bundle) {
        if ($bundle->getSetting('layout_builder_enabled')) {
          $enabled[$bundle->entityTypePlugin->getPluginId() . ':' . $bundle->entity->id()] = $bundle;
        }
      }
    }

    // The user:user bundle is globally enabled, so we need to add it here.
    if (!empty($enabled) && !array_key_exists('user:user', $enabled)) {
      if ($user_plugin = $this->gqlEntityTypeManager()->getPluginInstance('user')) {
        if ($user_bundle = $user_plugin->getBundle('user')) {
          $enabled['user:user'] = $user_bundle;
        }
      }
    }

    // @todo hooks here?
    return $enabled;
  }

  /**
   * Check if a bundle has layout builder enabled.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_id
   *   The bundle id.
   *
   * @return \Drupal\layout_builder\LayoutBuilderEnabledInterface[]
   *   Layout builder enabled view displays.
   */
  public function getLayoutBuilderViewDisplays(string $entity_type_id, string $bundle_id): array {

    $view_modes = $this->entityDisplayRepository()->getViewModes($entity_type_id);
    $view_modes = array_keys($view_modes);
    $view_modes[] = 'default';

    // Map the view modes to display ids.
    $display_ids = array_map(
      fn($view_mode) => $entity_type_id . '.' . $bundle_id . '.' . $view_mode,
      $view_modes
    );

    // Load all the displays for the entity type.
    $view_displays = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->loadMultiple($display_ids);

    // Filter out disabled.
    $view_displays = array_filter(
      $view_displays,
      fn(EntityViewDisplayInterface $view_display) => $view_display->status()
    );

    // Filter out any displays that don't have a layout builder enabled.
    $layout_displays = array_filter(
      $view_displays,
      fn($view_display) => $view_display instanceof LayoutBuilderEnabledInterface && $view_display->isLayoutBuilderEnabled()
    );

    // Key layout_displays by the display mode.
    $layout_displays = array_combine(
      array_map(
        fn($view_display) => $view_display->getMode(),
        $layout_displays
      ),
      $layout_displays
    );

    return $layout_displays;
  }

  /**
   * Get the SDL for a layout builder field type.
   *
   * @param \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface $field
   *   The field.
   *
   * @return string
   *   The SDL type.
   */
  public function getLayoutBuilderFieldTypeSdl(GraphQLComposeFieldTypeInterface $field): string {
    if ($field->isBaseField()) {
      // Base fields are common to all bundles.
      return u($field->getNameSdl())
        ->title()
        ->prepend($field->getEntityWrapper()->entityTypePlugin->getTypeSdl())
        ->prepend('BlockField')
        ->toString();
    }

    // Bundle specific fields.
    return u($field->getNameSdl())
      ->title()
      ->prepend($field->getEntityWrapper()->getTypeSdl())
      ->prepend('BlockField')
      ->toString();
  }

  /**
   * Get the field definition off the block instance.
   *
   * @param \Drupal\layout_builder\Plugin\Block\FieldBlock $block_instance
   *   The block instance.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface|null
   *   The field plugin instance.
   */
  protected function getBlockFieldPluginInstance(FieldBlock $block_instance): ?GraphQLComposeFieldTypeInterface {
    [, $entity_type_id, $bundle_id, $field_name] = explode(FieldBlock::DERIVATIVE_SEPARATOR, $block_instance->getPluginId(), 4);

    $entity_plugin = $this->gqlEntityTypeManager()->getPluginInstance($entity_type_id);
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);

    if ($entity_plugin) {
      $bundle = $entity_plugin->getBundle($bundle_id);

      if ($bundle) {
        $fields = $this->gqlFieldTypeManager()->getBundleFields($entity_type_id, $bundle_id);
      }
    }

    // Users select how they want to load entities.
    $expose_entity_ids = $this->configFactory()
      ->get('graphql_compose.settings')
      ->get('settings.expose_entity_ids');

    // Remap raw field id back to EG nid = uuid.
    // Caters to the uuid flip switch.
    if (!$expose_entity_ids && $entity_type->hasKey('id')) {
      $id_key = $entity_type->getKey('id');
      if ($field_name === $id_key) {
        $field_name = 'uuid';
      }
    }

    return $fields[$field_name] ?? NULL;
  }

}
