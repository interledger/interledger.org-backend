<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface;

/**
 * Manager that collects and exposes GraphQL compose field type plugins.
 *
 * A field type is a plugin that defines how to resolve a Drupal Field Type.
 *
 * @package Drupal\graphql_compose
 */
class GraphQLComposeFieldTypeManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Private field plugin storage.
   *
   * @var array
   */
  private array $fields = [];

  /**
   * Private field plugin storage.
   *
   * @var array
   */
  private array $interfaceFields = [];

  /**
   * GraphQLComposeFieldTypeManager constructor.
   *
   * @param bool|string $pluginSubdirectory
   *   The plugin's subdirectory.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string|null $plugin_interface
   *   The interface each plugin should implement.
   * @param string $plugin_definition_annotation_name
   *   The name of the annotation that contains the plugin definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager $gqlEntityTypeManager
   *   Entity type plugin manager.
   * @param array $config
   *   The configuration service parameter.
   */
  public function __construct(
    $pluginSubdirectory,
    \Traversable $namespaces,
    ModuleHandlerInterface $module_handler,
    $plugin_interface,
    $plugin_definition_annotation_name,
    CacheBackendInterface $cache_backend,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ConfigFactoryInterface $configFactory,
    protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager,
    array $config
  ) {
    parent::__construct(
      $pluginSubdirectory,
      $namespaces,
      $module_handler,
      $plugin_interface,
      $plugin_definition_annotation_name
    );

    $this->alterInfo('graphql_compose_field_type');

    $this->useCaches(empty($config['development']));
    $this->setCacheBackend($cache_backend, 'graphql_compose_field_type', [
      'graphql_compose_field_type',
      'config:field_config_list',
    ]);
  }

  /**
   * Get field plugin instances for an entity type.
   *
   * @todo check how efficient this is.
   */
  public function getFieldInstance(array $config = []): ?GraphQLComposeFieldTypeInterface {
    if (!isset($config['field_definition'])) {
      throw new \Exception('Field config missing field_definition');
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $config['field_definition'];

    $config = array_merge([
      'field_name'  => $field_definition->getName(),
      'field_type'  => $field_definition->getType(),
      'description' => strval($field_definition->getDescription() ?: $field_definition->getLabel()),
      'name_sdl'    => $field_definition->getName(),
      'multiple'    => $field_definition->getFieldStorageDefinition()->isMultiple(),
      'required'    => $field_definition->isRequired(),
    ], $config);

    return $this->createFieldInstance($config['field_type'], $config);
  }

  /**
   * Get field plugin instances for an entity type.
   *
   * @todo check how efficient this is.
   */
  public function getPropertyInstance(array $config = []): ?GraphQLComposeFieldTypeInterface {
    if (!isset($config['field_name'])) {
      throw new \Exception('Property config missing field_name');
    }

    $config = array_merge([
      'field_type' => 'property',
      'name_sdl'   => $config['name_sdl'] ?? $config['field_name'],
      'type_sdl'   => $config['type_sdl'] ?? 'String',
    ], $config);

    return $this->createFieldInstance($config['field_type'], $config);
  }

  /**
   * Create a field type plugin with config.
   *
   * @param string $field_type_plugin_id
   *   The plugin ID for a custom field type.
   * @param array $config
   *   Configureation for the plugin.
   */
  protected function createFieldInstance(string $field_type_plugin_id, array $config): ?GraphQLComposeFieldTypeInterface {
    if (!$plugin_definition = $this->getDefinition($field_type_plugin_id, FALSE)) {
      // This type may be referenced by an entity and
      // not have a defined plugin in GraphqlCompose/FieldType.
      return NULL;
    }

    // Allow a plugin to override config.
    foreach (array_keys($config) as $config_key) {
      if (array_key_exists($config_key, $plugin_definition)) {
        $config[$config_key] = $plugin_definition[$config_key];
      }
    }

    return $this->createInstance($field_type_plugin_id, $config);
  }

  /**
   * Fetch base fields for an entity type.
   */
  public function getBaseFields(string $entity_type_id): array {
    $definition = $this->gqlEntityTypeManager->getDefinition($entity_type_id);

    $base_fields = $definition['base_fields'] ?? [];

    $this->moduleHandler->invokeAll('graphql_compose_entity_base_fields_alter', [
      &$base_fields,
      $entity_type_id,
    ]);

    return $base_fields;
  }

  /**
   * Get fields for a bundle.
   */
  public function getBundleFields(string $entity_type_id, string $bundle_id): array {
    if (isset($this->fields[$entity_type_id][$bundle_id])) {
      return $this->fields[$entity_type_id][$bundle_id];
    }

    $fields = [];

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($entity_type_id);

    $base_fields = $this->getBaseFields($entity_type_id);

    $config = $this->configFactory->get('graphql_compose.settings');

    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);

      foreach ($field_definitions as $field_name => $field_definition) {
        $enabled = FALSE;

        $options = [
          'entity' => $entity_plugin_type->getBundle($bundle_id),
          'field_definition' => $field_definition,
        ];

        // Add base fields defined in the entity type.
        $is_base_field = array_key_exists($field_name, $base_definitions);
        $is_base_enabled = array_key_exists($field_name, $base_fields);

        if ($is_base_field && $is_base_enabled) {
          $enabled = TRUE;
          $options = array_merge(
            $options,
            // Default to required true.
            ['required' => TRUE],
            // Allow entity types to override field config.
            $base_fields[$field_name],
          );
        }
        else {
          // Allow overriding of the field name via config.
          $config_base = 'field_config.' . $entity_type_id . '.' . $bundle_id . '.' . $field_name;

          // Field has been enabled by user in the UI.
          $enabled = $config->get($config_base . '.enabled') ?: FALSE;

          if ($name_sdl = $config->get($config_base . '.name_sdl') ?: NULL) {
            $options['name_sdl'] = $name_sdl;
          }
        }

        // Add field to result.
        if ($enabled) {
          if ($instance = $this->getFieldInstance($options)) {
            $fields[$field_name] = $instance;
          }
        }
      }
    }
    else {
      foreach ($base_fields as $field_name => $config) {
        $config['entity'] = $entity_plugin_type->getBundle($bundle_id);
        $config['field_name'] = $field_name;

        if ($instance = $this->getPropertyInstance($config)) {
          $fields[$field_name] = $instance;
        }
      }
    }

    $this->fields[$entity_type_id][$bundle_id] = $fields;

    return $this->fields[$entity_type_id][$bundle_id];
  }

  /**
   * Return fields for usage in interface.
   *
   * @return Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface[]
   *   Field instances.
   */
  public function getInterfaceFields(string $entity_type_id): array {
    if (isset($this->interfaceFields[$entity_type_id])) {
      return $this->interfaceFields[$entity_type_id];
    }

    $fields = [];

    $base_fields = $this->getBaseFields($entity_type_id);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

      foreach ($base_definitions as $field_name => $field_definition) {
        if (array_key_exists($field_name, $base_fields)) {
          $config = ['field_definition' => $field_definition];
          $config = array_merge($base_fields[$field_name], $config);

          if ($instance = $this->getFieldInstance($config)) {
            $fields[$field_name] = $instance;
          }
        }
      }
    }

    $this->interfaceFields[$entity_type_id] = $fields;

    return $this->interfaceFields[$entity_type_id];
  }

}
