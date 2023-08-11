<?php

/**
 * @file
 * Hooks provided by GraphQL Compose metatags module.
 */

/**
 * Metatag union type resolver alter.
 *
 * Add the ability to resolve your custom metatag types
 * within the MetatagUnion type.
 *
 * @param mixed $value
 *   The value being resolved.
 * @param string|null $type
 *   The type to resolve to.
 */
function hook_graphql_compose_metatags_union_alter($value, ?string &$type): void {
  if ($value['tag'] === 'custom') {
    $type = 'MetatagCustom';
  }
}
