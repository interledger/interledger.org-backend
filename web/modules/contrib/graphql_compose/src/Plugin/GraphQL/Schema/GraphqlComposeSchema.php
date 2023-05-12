<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\Schema;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\graphql\Plugin\SchemaExtensionPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use GraphQL\Language\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;

/**
 * The provider of the schema base for the GraphQL Compose GraphQL API.
 *
 * Provides a target schema for GraphQL Schema extensions. Schema Extensions
 * should implement `SdlSchemaExtensionPluginBase` and should not subclass this
 * class.
 *
 * @Schema(
 *   id = "graphql_compose",
 *   name = "GraphQL Compose Schema"
 * )
 */
class GraphqlComposeSchema extends SdlSchemaPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Field type plugin manager.
   */
  protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager;

  /**
   * Entity type plugin manager.
   */
  protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager;

  /**
   * Drupal entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->gqlEntityTypeManager = $container->get('graphql_compose.entity_type_manager');
    $instance->gqlSchemaTypeManager = $container->get('graphql_compose.schema_type_manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    return new ResolverRegistry();
  }

  /**
   * {@inheritdoc}
   *
   * Inject extensions into the schema after all extensions loaded.
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

    $schema = array_filter(array_merge(
      [$this->gqlSchemaTypeManager->printTypes()],
      [$this->getSchemaDefinition()],
      $extensions
    ));

    $options = ['noLocation' => TRUE];
    $ast = !empty($schema) ? Parser::parse(implode(PHP_EOL . PHP_EOL, $schema), $options) : NULL;
    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }

    return $ast;
  }

  /**
   * {@inheritdoc}
   *
   * Inject extensions into the schema after all extensions loaded.
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

    $schema = array_filter(array_merge(
      [$this->gqlSchemaTypeManager->printExtensions()],
      $extensions
    ));

    $options = ['noLocation' => TRUE];
    $ast = !empty($schema) ? Parser::parse(implode(PHP_EOL . PHP_EOL, $schema), $options) : NULL;
    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }

    return $ast;
  }

}
