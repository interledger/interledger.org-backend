<?php

namespace Drupal\graphql_compose_statistics\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_compose_edges_extra\EnabledBundlesTrait;

/**
 * Add route resolution.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_statistics_schema",
 *   name = "Statistics Schema Extension",
 *   description = @Translation("MUtation to update node count."),
 *   schema = "graphql_compose"
 * )
 */
class StatisticsSchemaExtension extends SdlSchemaExtensionPluginBase
{

  use EnabledBundlesTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function registerResolvers(ResolverRegistryInterface $registry)
  {
    $builder = new ResolverBuilder();


    $registry->addFieldResolver(
      'Mutation',
      'recordView',
      $builder->produce('graphql_compose_statistics_record_view')
        ->map('id', $builder->fromArgument('id'))
    );
  }
}
