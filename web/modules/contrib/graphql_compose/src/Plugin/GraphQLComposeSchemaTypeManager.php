<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

/**
 * Manage graphql types for lazy loading schema creation.
 *
 * A schema type is a plugin that defines how to resolve a GraphQL Type.
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
   * Constructs a GraphQLComposeSchemaTypeManager object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
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
    protected LoggerChannelFactoryInterface $loggerFactory,
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
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeInterface
   *   The plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeInterface $instance */
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
   *
   * @param \GraphQL\Type\Definition\Type $type
   *   The type to store.
   *
   * @return \GraphQL\Type\Definition\Type
   *   The stored type.
   *
   * @throws \Exception
   *   If the type does not have a name.
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
   * @param string $name
   *   The name of the type.
   * @param bool $multiple
   *   Whether the type is a list.
   * @param bool $required
   *   Whether the type is required.
   *
   * @return \GraphQL\Type\Definition\Type
   *   The GraphQL type.
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
          $this->loggerFactory->get('graphql_compose')->warning($message, [
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
   *
   * @param \GraphQL\Type\Definition\Type $type
   *   The type to extend.
   *
   * @return \GraphQL\Type\Definition\Type
   *   The extended type.
   */
  public function extend(Type $type): Type {

    // This is a good spot for a hook probably. Hit me up if you want it.
    $this->extensions[] = $type;
    return $type;
  }

  /**
   * Utility function to get current defined types.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   The types.
   */
  public function getTypes(): array {
    return $this->types;
  }

  /**
   * Utility function to get current extensions.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   The extensions.
   */
  public function getExtensions(): array {
    return $this->extensions;
  }

  /**
   * Print types as GraphQL strings.
   *
   * @return string|null
   *   The GraphQL schema types.
   */
  public function printTypes(): ?string {

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
   *
   * @return string|null
   *   The GraphQL schema type extensions.
   */
  public function printExtensions(): ?string {

    // Allow types to extend only interfaces.
    // Strip empty {} blocks.
    $print = function (Type $type) {
      $printed = SchemaPrinter::printType($type);
      return preg_replace('/\{[\s\r\n\t]+\}/', '', $printed);
    };

    $types = array_map(
      fn($type) => 'extend ' . $print($type),
      $this->extensions
    );

    return trim(implode(PHP_EOL . PHP_EOL, $types)) ?: NULL;
  }

}
