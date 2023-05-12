<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase as GSdlSchemaExtensionPluginBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Entity Type GraphQL Compose plugins to the GraphQL API.
 */
abstract class SdlSchemaExtensionPluginBase extends GSdlSchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Entity field manager.
   */
  protected EntityFieldManager $entityFieldManager;

  /**
   * Entity type plugin manager.
   */
  protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager;

  /**
   * Field type plugin manager.
   */
  protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager;

  /**
   * SDL type plugin manager.
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
      $plugin_definition,
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->gqlEntityTypeManager = $container->get('graphql_compose.entity_type_manager');
    $instance->gqlFieldTypeManager = $container->get('graphql_compose.field_type_manager');
    $instance->gqlSchemaTypeManager = $container->get('graphql_compose.schema_type_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * Satisfy interface. We may not need to resolve anything if field based.
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
  }

  /**
   * {@inheritdoc}
   *
   * Remove throw.
   */
  protected function loadDefinitionFile($type) {
    try {
      return parent::loadDefinitionFile($type);
    }
    catch (InvalidPluginDefinitionException $e) {
      // Ignore this.
    }
  }

}
