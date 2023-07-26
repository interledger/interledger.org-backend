<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_blocks\Plugin\GraphQL\SchemaExtension;

use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use GraphQL\Error\UserError;

/**
 * Add blocks to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_blocks_schema",
 *   name = "GraphQL Compose Blocks",
 *   description = "Add blocks to the Schema.",
 *   schema = "graphql_compose"
 * )
 */
class BlocksSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver(
      'Query',
      'block',
      $builder->produce('block_load')
        ->map('id', $builder->fromArgument('id'))
    );

    $block_plugin_types = [
      'BlockPlugin',
      'BlockContent',
    ];

    foreach ($block_plugin_types as $plugin_id) {

      // Block plugin ID.
      $registry->addFieldResolver(
        $plugin_id,
        'id',
        $builder->callback(fn (BlockPluginInterface $block) => $block->getPluginId())
      );

      // Block derivative ID.
      $registry->addFieldResolver(
        $plugin_id,
        'title',
        $builder->callback(function (BlockPluginInterface $block) {
          $config = $block->getConfiguration();
          $display = $config['label_display'] ?? FALSE;
          return $display ? $block->label() : NULL;
        })
      );

      // Block derivative ID.
      $registry->addFieldResolver(
        $plugin_id,
        'render',
        $builder->produce('block_render')
          ->map('block_instance', $builder->fromParent())
      );
    }

    // Block derivative entity.
    $registry->addFieldResolver(
      'BlockContent',
      'entity',
      $builder->produce('block_entity_load')
        ->map('block_instance', $builder->fromParent())
    );

    // Type Resolver.
    $registry->addTypeResolver(
      'BlockUnion',
      function ($value) {
        if ($value instanceof BlockContentBlock) {
          return 'BlockContent';
        }
        if ($value instanceof BlockPluginInterface) {
          return 'BlockPlugin';
        }
        throw new UserError('Could not resolve block union type.');
      }
    );
  }

}
