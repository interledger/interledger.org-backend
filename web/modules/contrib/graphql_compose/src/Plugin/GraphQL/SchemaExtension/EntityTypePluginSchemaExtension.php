<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Entity Type GraphQL Compose plugins to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_entity_type_schema_extension",
 *   name = "GraphQL Compose Entity Types",
 *   description = "Entity types defined by plugins.",
 *   schema = "graphql_compose"
 * )
 */
class EntityTypePluginSchemaExtension extends SdlSchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $pathAliasManager;

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

    $instance->configFactory = $container->get('config.factory');
    $instance->pathAliasManager = $container->get('path_alias.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver(
      'Query',
      'info',
      $builder->callback(fn () => TRUE)
    );

    $registry->addFieldResolver(
      'SchemaInformation',
      'description',
      $builder->callback(function () {
        return $this->configFactory->get('graphql_compose.settings')->get('settings.schema_description') ?: NULL;
      })
    );

    $registry->addFieldResolver(
      'SchemaInformation',
      'version',
      $builder->callback(function () {
        return $this->configFactory->get('graphql_compose.settings')->get('settings.schema_version') ?: NULL;
      })
    );

    if ($this->configFactory->get('graphql_compose.settings')->get('settings.site_front')) {
      $registry->addFieldResolver(
        'SchemaInformation',
        'home',
        $builder->callback(function () {
          $path = $this->configFactory->get('system.site')->get('page.front') ?: NULL;

          return $path ? $this->pathAliasManager->getAliasByPath($path) : NULL;
        })
      );
    }

    if ($this->configFactory->get('graphql_compose.settings')->get('settings.site_slogan')) {
      $registry->addFieldResolver(
        'SchemaInformation',
        'slogan',
        $builder->callback(fn () => $this->configFactory->get('system.site')->get('slogan') ?: NULL)
      );
    }

    if ($this->configFactory->get('graphql_compose.settings')->get('settings.site_name')) {
      $registry->addFieldResolver(
        'SchemaInformation',
        'name',
        $builder->callback(fn () => $this->configFactory->get('system.site')->get('name') ?: NULL)
      );
    }

    // Utility for junk.
    $registry->addFieldResolver(
      'UnsupportedType',
      'unsupported',
      $builder->callback(fn () => TRUE),
    );

    $registry->addFieldResolver(
      'Mutation',
      '_',
      $builder->callback(fn () => TRUE),
    );

    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      $entity_type->registerResolvers($registry, $builder);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Load type's SDL into the manager.
   */
  public function getBaseDefinition() {
    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      $entity_type->registerTypes();
    }

    return NULL;
  }

}
