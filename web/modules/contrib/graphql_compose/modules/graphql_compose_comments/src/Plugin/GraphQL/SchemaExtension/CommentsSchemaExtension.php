<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_compose_comments\CommentableTrait;

/**
 * Add image styles to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_comments_schema",
 *   name = "GraphQL Compose Comments",
 *   description = @Translation("Add comments extras to the Schema."),
 *   schema = "graphql_compose"
 * )
 */
class CommentsSchemaExtension extends SdlSchemaExtensionPluginBase {

  use CommentableTrait;

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    // Load with field resolver to avoid loading user when not requested.
    if ($this->moduleHandler->moduleExists('graphql_compose_users')) {
      $registry->addFieldResolver(
        'CommentAuthor',
        'user',
        $builder->compose(
          $builder->callback(function ($parent) {
            return $parent['user'];
          }),

          $builder->produce('entity_load')
            ->map('id', $builder->fromParent())
            ->map('type', $builder->fromValue('user'))
        ),
      );
    }

    $bundles = $this->gqlEntityTypeManager->getPluginInstance('comment')->getBundles();
    foreach ($bundles as $bundle) {
      $mutation_name = $this->getMutationNameSdl($bundle);

      $registry->addFieldResolver(
        'Mutation',
        $mutation_name,
        $builder->compose(
          // Get the nested data type.. Eg NodePage.
          $builder->callback(function ($parent, $args) {
            return $args['data']['entityType'] ?? NULL;
          }),

          // Resolve NodePage into 'node:page'.
          $builder->produce('schema_enum_value')
            ->map('type', $builder->fromValue('CommentAvailable'))
            ->map('value', $builder->fromParent()),

          // Split the value into type and bundle.
          $builder->callback(function ($parent, $args) {
            $bits = explode(':', $parent);
            return ['type' => $bits[0], 'bundle' => $bits[1] ?? NULL];
          }),

           // Useful for extending this producer.
          $builder->context('entity_type', $builder->callback(fn($parent) => $parent['type'])),
          $builder->context('entity_bundles', $builder->callback(fn($parent) => [$parent['bundle']])),
          $builder->context('entity_id', $builder->callback(fn($parent, $args) => $args['data']['entityId'])),

          // Load entity to comment on.
          $builder->produce('entity_load_by_uuid_or_id')
            ->map('type', $builder->fromContext('entity_type'))
            ->map('bundles', $builder->fromContext('entity_bundles'))
            ->map('identifier', $builder->fromContext('entity_id')),

          $builder->produce('create_comment')
            ->map('data', $builder->fromArgument('data'))
            ->map('entity', $builder->fromParent())
        )
      );
    }
  }

}
