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
 *   description = @Translation("Add blocks to the Schema."),
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

    // Block plugin ID.
    $registry->addFieldResolver(
      'BlockInterface',
      'id',
      $builder->callback(fn (BlockPluginInterface $block) => $block->getPluginId())
    );

    // Block title.
    $registry->addFieldResolver(
      'BlockInterface',
      'title',
      $builder->callback(function (BlockPluginInterface $block) {
        $config = $block->getConfiguration();
        $display = $config['label_display'] ?? FALSE;
        return $display ? $block->label() : NULL;
      })
    );

    // Block render.
    $registry->addFieldResolver(
      'BlockInterface',
      'render',
      $builder->produce('block_render')
        ->map('block_instance', $builder->fromParent())
    );

    // Block content entity.
    $registry->addFieldResolver(
      'BlockContent',
      'entity',
      $builder->produce('block_content_entity_load')
        ->map('block_instance', $builder->fromParent())
    );

    // Type Resolver.
    $registry->addTypeResolver(
      'BlockUnion',
      function ($value) {

        // Generic fallback.
        if ($value instanceof BlockPluginInterface) {
          $type = 'BlockPlugin';
        }

        if ($value instanceof BlockContentBlock) {
          $type = 'BlockContent';
        }

        // Give opportunity to extend this union.
        $this->moduleHandler->invokeAll('graphql_compose_blocks_union_alter', [
          $value,
          &$type,
        ]);

        if ($type) {
          return $type;
        }

        throw new UserError('Could not resolve block union type.');
      }
    );
  }

}
