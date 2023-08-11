<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
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
 */
class GraphQLComposeFieldTypeManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Private field plugin storage.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface[]
   */
  private array $fields = [];

  /**
   * Private field plugin storage.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface[]
   */
  private array $interfaceFields = [];

  /**
   * Constructs a GraphQLComposeFieldTypeManager object.
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
   * Create a field plugin instances for an entity type field.
   *
   * @param array $config
   *   Configuration for the plugin.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface|null
   *   The field plugin instance.
   *
   * @throws \Exception
   *   If the field config is missing the field_definition.
   *
   * @todo Check how efficient this is.
   */
  public function getFieldInstance(array $config = []): ?GraphQLComposeFieldTypeInterface {
    if (!isset($config['field_definition'])) {
      throw new \Exception('Field config missing field_definition');
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $config['field_definition'];

    // Set required field config if not set yet.
    $config = array_merge([
      'description' => strval($field_definition->getDescription() ?: $field_definition->getLabel()),
      'field_name' => $field_definition->getName(),
      'field_type' => $field_definition->getType(),
      'name_sdl' => $field_definition->getName(),
      'required' => $field_definition->isRequired(),
      'multiple' => $field_definition->getFieldStorageDefinition()->isMultiple(),
    ], $config);

    return $this->createFieldInstance($config['field_type'], $config);
  }

  /**
   * Create a field plugin instance for an entity type property.
   *
   * @param array $config
   *   Configuration for the plugin.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface|null
   *   The field plugin instance.
   *
   * @throws \Exception
   *   If the field config is missing the field_name.
   *
   * @todo Check how efficient this is.
   */
  public function getPropertyInstance(array $config = []): ?GraphQLComposeFieldTypeInterface {
    if (!isset($config['field_name'])) {
      throw new \Exception('Property config missing field_name');
    }

    // Set required property config if not set yet.
    $config = array_merge([
      'field_type' => 'property',
      'name_sdl' => $config['field_name'],
      'type_sdl' => 'String',
    ], $config);

    return $this->createFieldInstance($config['field_type'], $config);
  }

  /**
   * Create a field type plugin with config.
   *
   * @param string $field_type_plugin_id
   *   The plugin ID for a custom field type.
   * @param array $config
   *   Configuration for the plugin.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface|null
   *   The field plugin instance.
   */
  protected function createFieldInstance(string $field_type_plugin_id, array $config): ?GraphQLComposeFieldTypeInterface {
    if (!$plugin_definition = $this->getDefinition($field_type_plugin_id, FALSE)) {
      // This type may be referenced by an entity and
      // not have a defined plugin in GraphqlCompose/FieldType.
      return NULL;
    }

    // Set missing config defaults if not set yet.
    $config = array_merge([
      'field_type' => 'property',
      'name_sdl' => $plugin_definition['name_sdl'] ?? NULL,
      'type_sdl' => $plugin_definition['type_sdl'] ?? NULL,
      'description' => $plugin_definition['description'] ?? NULL,
    ], $config);

    return $this->createInstance($field_type_plugin_id, $config);
  }

  /**
   * All defined fields that have been created at time of invocation.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface[]
   *   An array of fields that have been initialized.
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * Get fields for a bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface[]
   *   An array of fields for the chosen entity and bundle.
   */
  public function getBundleFields(string $entity_type_id, string $bundle_id): array {
    if (isset($this->fields[$entity_type_id][$bundle_id])) {
      return $this->fields[$entity_type_id][$bundle_id];
    }

    // Hydrate fields with interface fields.
    $fields = $this->getInterfaceFields($entity_type_id);

    // Get user config for fieldable fields.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($entity_type_id);

    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
      $settings = $this->configFactory->get('graphql_compose.settings');

      foreach ($field_definitions as $field_name => $field_definition) {

        // Check if field is a base-field.
        if (array_key_exists($field_name, $base_definitions) || array_key_exists($field_name, $fields)) {
          continue;
        }

        // Allow overriding field plugin config via config.
        $config_base = 'field_config.' . $entity_type_id . '.' . $bundle_id . '.' . $field_name;

        // Strip null values from config.
        $config = array_filter(
          $settings->get($config_base) ?: [],
          fn ($item) => !is_null($item)
        );

        // Bind the field definition to the config.
        $config['field_definition'] ??= $field_definition;

        // User config or false or null... is false.
        $enabled = $config['enabled'] ?? FALSE ?: FALSE;

        // Add field to result.
        if ($enabled) {
          if ($instance = $this->getFieldInstance($config)) {
            $fields[$field_name] = $instance;
          }
        }
      }
    }

    // Bind each field (and interface field) to the bundle entity.
    $bundle = $entity_plugin_type->getBundle($bundle_id);
    foreach ($fields as $field) {
      $field->setEntityWrapper($bundle);
    }

    // Sort the fields, totally necessary.
    $this->sortFields($fields);

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

    // Get base fields defined by the entity type plugin.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_plugin_type = $this->gqlEntityTypeManager->getPluginInstance($entity_type_id);
    $base_fields = $entity_plugin_type->getBaseFields();

    // If it's not fieldable, it's probably a plugin entity.
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

      foreach ($base_definitions as $field_name => $field_definition) {
        // Only enable fields defined in config.
        if (!array_key_exists($field_name, $base_fields)) {
          continue;
        }

        $config = array_merge(
          ['field_definition' => $field_definition],
          // Default to required true.
          ['required' => TRUE],
          // Allow entity types to override field config.
          $base_fields[$field_name],
        );

        if ($instance = $this->getFieldInstance($config)) {
          $fields[$field_name] = $instance;
        }
      }
    }
    else {
      // Add base fields verbatim.
      foreach ($base_fields as $field_name => $config) {

        $config = array_merge(
          ['field_name' => $field_name],
          // Default to required true.
          ['required' => TRUE],
          // Allow entity types to override field config.
          $config,
        );

        if ($instance = $this->getPropertyInstance($config)) {
          $fields[$field_name] = $instance;
        }
      }
    }

    // Combine fields with identifying fields, giving our definition priority.
    $fields = array_merge(
      $fields,
      $this->getIdentifyingFields($entity_type),
    );

    // Sort the fields, totally necessary.
    $this->sortFields($fields);

    $this->interfaceFields[$entity_type_id] = $fields;

    return $this->interfaceFields[$entity_type_id];
  }

  /**
   * Get identifying fields for the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The fields to use as IDs.
   */
  protected function getIdentifyingFields(EntityTypeInterface $entity_type): array {

    $fields = [];

    // Users select how they want to load entities.
    $expose_entity_ids = $this->configFactory
      ->get('graphql_compose.settings')
      ->get('settings.expose_entity_ids');

    // Check the base definitions for the entity type.
    $field_definitions = $entity_type->entityClassImplements(FieldableEntityInterface::class)
      ? $this->entityFieldManager->getBaseFieldDefinitions($entity_type->id())
      : [];

    // If loading by UUID, do not add entity ID automatically.
    if ($expose_entity_ids && $entity_type->hasKey('id')) {
      $id_key = $entity_type->getKey('id');

      $fields[$id_key] = $this->getPropertyInstance([
        'field_name' => $id_key,
        'field_type' => 'entity_id',
        'name_sdl' => 'id',
        'type_sdl' => 'ID',
        'required' => TRUE,
        'description' => (string) $this->t('The entity ID.'),
        'field_definition' => $field_definitions[$id_key] ?? NULL,
      ]);
    }

    // We always add UUID if available.
    // If expose_entity_ids, set name_sdl to id.
    // If not expose_entity_ids, set name_sdl to uuid.
    if ($entity_type->hasKey('uuid')) {
      $uuid_key = $entity_type->getKey('uuid');

      $fields['uuid'] = $this->getPropertyInstance([
        'field_name' => $uuid_key,
        'field_type' => 'uuid',
        'name_sdl' => $expose_entity_ids ? 'uuid' : 'id',
        'type_sdl' => 'ID',
        'required' => TRUE,
        'description' => (string) $this->t('The Universally Unique IDentifier (UUID).'),
        'field_definition' => $field_definitions[$uuid_key] ?? NULL,
      ]);
    }

    return $fields;
  }

  /**
   * Utility function to order the fields with IDs first then alphabetically.
   *
   * @param array $fields
   *   The fields to order.
   */
  protected function sortFields(array &$fields) {

    // Sort fields by name.
    uasort($fields, fn ($a, $b) => strnatcmp($a->getNameSdl(), $b->getNameSdl()));

    $fields = array_merge(
      // Put the id field first.
      array_filter($fields, fn ($field) => $field->getNameSdl() === 'id'),
      // Put UUID field second.
      array_filter($fields, fn ($field) => $field->getNameSdl() === 'uuid'),
      // Merge in alphabetical order.
      $fields,
    );
  }

}
