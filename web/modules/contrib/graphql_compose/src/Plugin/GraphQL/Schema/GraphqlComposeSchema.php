<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\Schema;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\graphql\Plugin\SchemaExtensionPluginInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use GraphQL\Language\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class GraphQLComposeSchema extends SdlSchemaPluginBase implements ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager
   */
  protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->gqlSchemaTypeManager = $container->get('graphql_compose.schema_type_manager');

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

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['settings_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Configure GraphQL Compose Schema'),
      '#url' => Url::fromRoute('graphql_compose.schema'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState): void {
    // Satisfy interface. Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState): void {
    // Satisfy interface. Nothing to do here.
  }

}
