<?php

namespace Drupal\graphql_compose_extra_search_api\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_compose_extra_edges\EnabledBundlesTrait;

/**
 * Add route resolution.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_extra_search_api_schema",
 *   name = "Search API Schema Extension",
 *   description = @Translation("Multiple query loading edge connections per entity type."),
 *   schema = "graphql_compose"
 * )
 */
class SearchApiSchemaExtension extends SdlSchemaExtensionPluginBase
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

    // Connection fields.
    $registry->addFieldResolver(
      'SearchApiConnection',
      'edges',
      $builder->produce('connection_edges')
        ->map('connection', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'SearchApiConnection',
      'nodes',
      $builder->produce('connection_nodes')
        ->map('connection', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'SearchApiConnection',
      'pageInfo',
      $builder->produce('connection_page_info')
        ->map('connection', $builder->fromParent())
    );

    // Edge fields.
    $registry->addFieldResolver(
      'Edge',
      'cursor',
      $builder->produce('edge_cursor')
        ->map('edge', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'Edge',
      'node',
      $builder->produce('edge_node')
        ->map('edge', $builder->fromParent())
    );

    $registry->addFieldResolver(
      'Query',
      'search',
      $builder->produce('graphql_compose_extra_search_api_edge')
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('langcode', $builder->fromArgument('langcode'))
        ->map('keywords', $builder->fromArgument('keywords'))
        ->map('searchIndex', $builder->fromArgument('searchIndex'))
    );

    $registry->addFieldResolver(
      'Query',
      'searchSuggestions',
      $builder->produce('graphql_compose_extra_search_api_suggestions')
        ->map('keywords', $builder->fromArgument('keywords'))
    );
  }
}
