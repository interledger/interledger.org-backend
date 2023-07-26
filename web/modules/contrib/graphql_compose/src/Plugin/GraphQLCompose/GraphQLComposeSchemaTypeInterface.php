<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use GraphQL\Type\Definition\Type;

/**
 * Defines a field type plugin that returns a field type part.
 */
interface GraphQLComposeSchemaTypeInterface {

  /**
   * Utility method to load another type by plugin id.
   *
   * @param string $plugin_id
   *   Plugin ID of an SDL type.
   *
   * @return \GraphQL\Type\Definition\Type
   *   GraphQL type.
   */
  public static function type(string $plugin_id): Type;

  /**
   * Add types to schema.
   *
   * Adds opportunity to add types dynamically.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Types to build into schema.
   */
  public function getTypes(): array;

  /**
   * Add types as extensions in schema.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   Extensions for types to build into schema.
   */
  public function getExtensions(): array;

}
