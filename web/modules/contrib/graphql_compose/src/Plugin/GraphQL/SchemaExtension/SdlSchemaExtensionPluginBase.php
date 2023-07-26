<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase as GSdlSchemaExtensionPluginBase;
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
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected EntityFieldManager $entityFieldManager;

  /**
   * Entity type plugin manager.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager
   */
  protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\graphql_compose\Plugin\GraphQLComposeFieldTypeManager
   */
  protected GraphQLComposeFieldTypeManager $gqlFieldTypeManager;

  /**
   * SDL type plugin manager.
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
   * We may not need to resolve anything if field based.
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    // Satisfy interface. Nothing to do here.
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
      // Ignore this exception from parent.
      // We are not concerned with the schema file not existing.
      return NULL;
    }
  }

}
