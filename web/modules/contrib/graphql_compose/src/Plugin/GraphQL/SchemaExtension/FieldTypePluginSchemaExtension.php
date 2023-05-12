<?php

namespace Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Adds Entity Type GraphQL Compose plugins to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_field_type_schema_extension",
 *   name = "GraphQL Compose Field Types",
 *   description = "Field types defined by plugins.",
 *   schema = "graphql_compose"
 * )
 */
class FieldTypePluginSchemaExtension extends SdlSchemaExtensionPluginBase
{

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry)
  {
    $builder = new ResolverBuilder();

    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      foreach ($entity_type->getBundles() as $key => $bundle) {
        $fields = $bundle->gqlFieldTypeManager->getBundleFields($bundle->entityTypePlugin->getBaseId(), $key);

        foreach ($fields as $field) {
          $field->registerResolvers($registry, $builder);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Load type's SDL into the manager.
   */
  public function getBaseDefinition()
  {
    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      foreach ($entity_type->getBundles() as $key => $bundle) {
        $fields = $bundle->gqlFieldTypeManager->getBundleFields($bundle->entityTypePlugin->getBaseId(), $key);

        foreach ($fields as $field) {
          $field->registerTypes();
        }
      }
    }

    return NULL;
  }
}
