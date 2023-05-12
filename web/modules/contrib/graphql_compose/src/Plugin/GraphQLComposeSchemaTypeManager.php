<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

/**
 * Manage graphql types for lazy loading schema creation.
 *
 * A schema type is a plugin that defines how to resolve a GraphQL Type.
 *
 * @package Drupal\graphql_compose
 */
class GraphQLComposeSchemaTypeManager extends DefaultPluginManager {

  /**
   * Static storage of types.
   *
   * @var \GraphQL\Type\Definition\Type[]
   */
  private array $types = [];

  /**
   * Static storage of extensions.
   *
   * @var \GraphQL\Type\Definition\Type[]
   */
  private array $extensions = [];

  /**
   * GraphQLComposeSchemaTypeManager constructor.
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

    $this->alterInfo('graphql_compose_graphql_type');
    $this->useCaches(empty($config['development']));
    $this->setCacheBackend($cache_backend, 'graphql_compose_graphql_type', ['graphql_compose_graphql_type']);
  }

  /**
   * Hijack the createInstance to store the types and extensions.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase $instance */
    $instance = parent::createInstance($plugin_id, $configuration);

    foreach ($instance->getTypes() as $type) {
      $this->add($type);
    }

    // Allow types to define extensions.
    foreach ($instance->getExtensions() as $extension) {
      $this->extend($extension);
    }

    return $instance;
  }

  /**
   * Store an instance of a type in the registry.
   */
  public function add(Type $type): Type {
    if (!$type->name) {
      throw new \Exception('GraphQL Type missing name property.');
    }

    return $this->types[$type->name] ??= $type;
  }

  /**
   * Get a type by name, or load it up.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function get(string $name, bool $multiple = FALSE, bool $required = FALSE): Type {
    $standardTypes = Type::getStandardTypes();

    if (in_array($name, $standardTypes)) {
      $type = $standardTypes[$name];
    }
    else {
      if (!array_key_exists($name, $this->types)) {
        try {
          $this->createInstance($name);
        }
        catch (PluginNotFoundException $e) {
          // This type may be referenced by a field, but not enabled in GUI.
          // or may not have a defined plugin in GraphqlCompose/SchemaType.
          $message = 'Type @name not found (perhaps no bundle is enabled?), replacing with UnsupportedType.';
          \Drupal::logger('graphql_compose')->warning($message, [
            '@name' => $name,
          ]);
          $name = 'UnsupportedType';
          $this->createInstance($name);
        }
      }

      $type = $this->types[$name];
    }

    // Extra utility for fields.
    if ($multiple) {
      $type = Type::listOf(Type::nonNull($type));
    }

    if ($required) {
      $type = Type::nonNull($type);
    }

    return $type;
  }

  /**
   * Add an extension to the registry.
   */
  public function extend(Type $type): Type {

    // This is a good spot for a hook probably. Hit me up if you want it.
    $this->extensions[] = $type;
    return $type;
  }

  /**
   * Utility function to get current defined types.
   */
  public function getTypes() {
    return $this->types;
  }

  /**
   * Utility function to get current estensions.
   */
  public function getExtensions() {
    return $this->extensions;
  }

  /**
   * Print types as GraphQL strings.
   */
  public function printTypes() {

    // Load all types.
    foreach ($this->getDefinitions() as $definition) {
      $this->get($definition['id']);
    }

    $schema = new Schema([
      'types' => $this->types,
    ]);

    $printed = SchemaPrinter::doPrint($schema);

    return trim($printed) ?: NULL;
  }

  /**
   * Print extensions as GraphQL strings.
   */
  public function printExtensions() {

    $types = array_map(
      fn($type) => 'extend ' . SchemaPrinter::printType($type),
      $this->extensions
    );

    return trim(implode(PHP_EOL . PHP_EOL, $types)) ?: NULL;
  }

}
