<?php

namespace Drupal\graphql_compose_extra_node_type\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\node\NodeTypeInterface;

/**
 * Add blocks to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_extra_node_type_schema",
 *   name = "GraphQL Compose Node Type",
 *   description = "Add node types to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class NodeTypeSchemaExtension extends SdlSchemaExtensionPluginBase
{

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry)
  {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver(
      'NodeType',
      'id',
      $builder->callback(fn (NodeTypeInterface $nodeType) => $nodeType->getEntityTypeId())
    );

    // Block derivative ID.
    $registry->addFieldResolver(
      'NodeType',
      'name',
      $builder->callback(fn (NodeTypeInterface $nodeType) => $nodeType->label())
    );
  }
}
