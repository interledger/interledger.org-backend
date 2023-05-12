<?php

namespace Drupal\graphql\Plugin\GraphQL\Schema;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\graphql\Event\AlterSchemaDataEvent;
use Drupal\graphql\Event\AlterSchemaExtensionDataEvent;
use Drupal\graphql\Plugin\SchemaExtensionPluginInterface;
use GraphQL\Language\Parser;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\graphql\Plugin\SchemaExtensionPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows to alter the graphql files data before parsing.
 *
 * @see \Drupal\graphql\Event\AlterSchemaDataEvent
 * @see \Drupal\graphql\Event\AlterSchemaExtensionDataEvent
 *
 * @Schema(
 *   id = "alterable_composable",
 *   name = "Alterable composable schema"
 * )
 */
class AlterableComposableSchema extends ComposableSchema {

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $dispatcher;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.graphql.ast'),
      $container->get('module_handler'),
      $container->get('plugin.manager.graphql.schema_extension'),
      $container->getParameter('graphql.config'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * AlterableComposableSchema constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Cache\CacheBackendInterface $astCache
   *   The cache bin for caching the parsed SDL.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\graphql\Plugin\SchemaExtensionPluginManager $extensionManager
   *   The schema extension plugin manager.
   * @param array $config
   *   The service configuration.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher
   *   The event dispatcher.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    CacheBackendInterface $astCache,
    ModuleHandlerInterface $moduleHandler,
    SchemaExtensionPluginManager $extensionManager,
    array $config,
    ContainerAwareEventDispatcher $dispatcher
  ) {
    parent::__construct(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $astCache,
      $moduleHandler,
      $extensionManager,
      $config
    );
    $this->dispatcher = $dispatcher;
  }

  /**
   * Retrieves the parsed AST of the schema definition.
   *
   * Almost copy of the original method except it
   * provides alter schema event in order to manipulate data.
   *
   * @param array $extensions
   *   The Drupal GraphQl schema plugins data.
   *
   * @return \GraphQL\Language\AST\DocumentNode
   *   The parsed schema document.
   *
   * @throws \GraphQL\Error\SyntaxError
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @see \Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema::getSchemaDocument()
   */
  protected function getSchemaDocument(array $extensions = []) {
    // Only use caching of the parsed document if we aren't in development mode.
    $cid = "schema:{$this->getPluginId()}";
    if (empty($this->inDevelopment) && $cache = $this->astCache->get($cid)) {
      return $cache->data;
    }

    $extensions = array_filter(array_map(function (SchemaExtensionPluginInterface $extension) {
      return $extension->getBaseDefinition();
    }, $extensions), function ($definition) {
      return !empty($definition);
    });
    $schema = array_merge([$this->getSchemaDefinition()], $extensions);
    // Event in order to alter the schema data.
    $event = new AlterSchemaDataEvent($schema);
    $this->dispatcher->dispatch(
      $event,
      AlterSchemaDataEvent::EVENT_NAME
    );
    $ast = Parser::parse(implode("\n\n", $event->getSchemaData()));
    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }
    return $ast;
  }

  /**
   * Retrieves the parsed AST of the schema extension definitions.
   *
   * Almost copy of the original method except it
   * provides alter schema extension event in order to manipulate data.
   *
   * @param array $extensions
   *   The Drupal GraphQl extensions data.
   *
   * @return \GraphQL\Language\AST\DocumentNode|null
   *   The parsed schema document.
   *
   * @throws \GraphQL\Error\SyntaxError
   *
   * @see \Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema::getSchemaDocument()
   */
  protected function getExtensionDocument(array $extensions = []) {
    // Only use caching of the parsed document if we aren't in development mode.
    $cid = "extension:{$this->getPluginId()}";
    if (empty($this->inDevelopment) && $cache = $this->astCache->get($cid)) {
      return $cache->data;
    }

    $extensions = array_filter(array_map(function (SchemaExtensionPluginInterface $extension) {
      return $extension->getExtensionDefinition();
    }, $extensions), function ($definition) {
      return !empty($definition);
    });

    // Event in order to alter the schema extension data.
    $event = new AlterSchemaExtensionDataEvent($extensions);
    $this->dispatcher->dispatch(
      $event,
      AlterSchemaExtensionDataEvent::EVENT_NAME
    );
    $ast = !empty($extensions) ? Parser::parse(implode("\n\n", $event->getSchemaExtensionData())) : NULL;
    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }

    return $ast;
  }

}
