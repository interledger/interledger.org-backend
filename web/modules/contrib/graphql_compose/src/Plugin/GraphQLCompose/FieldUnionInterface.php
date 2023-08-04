<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

/**
 * Extension for field plugin to enable unions.
 */
interface FieldUnionInterface {

  /**
   * Check if this field should be a generic union.
   *
   * @return bool
   *   True if enabled.
   */
  public function isGenericUnion(): bool;

  /**
   * Check if this field's union will return just a single type.
   *
   * @return bool
   *   True if single type.
   */
  public function isSingleUnion(): bool;

  /**
   * The GraphQL union type for this field (non generic).
   *
   * @return string
   *   Bundle in format of {Entity}{Bundle}{Fieldname}Union
   */
  public function getUnionTypeSdl(): string;

  /**
   * Get the target types for target type unions.
   *
   * @return array
   *   Schema types available to union.
   */
  public function getUnionTypeMapping(): array;

}
