<?php

/**
 * @file
 * Hooks provided by GraphQL Compose blocks module.
 */

/**
 * Block union type resolver alter.
 *
 * Add the ability to resolve your custom block types
 * within the BlockUnion type.
 *
 * @param mixed $value
 *   The value being resolved.
 * @param string|null $type
 *   The type to resolve to.
 */
function hook_graphql_compose_blocks_union_alter($value, ?string &$type): void {
  if ($value instanceof \Drupal\layout_builder\Plugin\Block\FieldBlock) {
    $type = 'BlockField';
  }
}
