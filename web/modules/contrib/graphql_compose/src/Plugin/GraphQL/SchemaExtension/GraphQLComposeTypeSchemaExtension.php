<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Adds Schema Types defined by the GraphQL compose plugin system.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_type_schema_extension",
 *   name = "GraphQL Compose Types",
 *   description = @Translation("GraphQL types defined by plugins."),
 *   schema = "graphql_compose"
 * )
 */
class GraphQLComposeTypeSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   *
   * Load entity types into schema.
   * This will daisy-chain the loading of all connected types.
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    // Register entities, their fields.
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
