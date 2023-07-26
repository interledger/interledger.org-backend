<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Wrapper\EntityTypeWrapper;

/**
 * Defines a field type plugin that returns a field type part.
 */
interface GraphQLComposeFieldTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Retrieves the producers for a field.
   *
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\Composite
   *   The composite resolver.
   */
  public function getProducers(ResolverBuilder $builder): Composite;

  /**
   * Set the entity wrapper for a field.
   *
   * @param \Drupal\graphql_compose\Wrapper\EntityTypeWrapper $entity_wrapper
   *   The entity wrapper.
   */
  public function setEntityWrapper(EntityTypeWrapper $entity_wrapper): void;

  /**
   * Get the wrapped entity type assigned to this field.
   *
   * @return \Drupal\graphql_compose\Wrapper\EntityTypeWrapper|null
   *   The wrapped entity type.
   */
  public function getEntityWrapper(): ?EntityTypeWrapper;

  /**
   * Get field definition for this field plugin instance.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   A field definition.
   */
  public function getFieldDefinition(): ?FieldDefinitionInterface;

  /**
   * Get the field_type value for this field plugin instance.
   *
   * @return string
   *   Field type as a string.
   */
  public function getFieldType(): string;

  /**
   * Get the field_name value for this field plugin instance.
   *
   * @return string
   *   Field name as a string.
   */
  public function getFieldName(): string;

  /**
   * Get the description value for this field plugin instance.
   *
   * @return string
   *   Field description as a string.
   */
  public function getDescription(): ?string;

  /**
   * Plugin field is multiple.
   *
   * @return bool
   *   True if field is multiple.
   */
  public function isMultiple(): bool;

  /**
   * Plugin field is required.
   *
   * @return bool
   *   True if field is required.
   */
  public function isRequired(): bool;

  /**
   * Plugin field is a base field.
   *
   * @return bool
   *   True if field is a base field.
   */
  public function isBaseField(): bool;

  /**
   * The GraphQL name for this field.
   *
   * @return string
   *   The GraphQL name for this field.
   */
  public function getNameSdl(): string;

  /**
   * The GraphQL type for this field.
   *
   * @return string
   *   The GraphQL type for this field.
   */
  public function getTypeSdl(): string;

  /**
   * The GraphQL query arguments for this field.
   *
   * In some edge cases you'll need to define args on the field.
   * Example: a field that returns a Connection type and requires args.
   *
   * This should return an array of GraphQL type args.
   * Example: ['a' => 'Int', 'b' => 'String']
   *
   * @return array
   *   The GraphQL query arguments for this field.
   */
  public function getArgsSdl(): array;

}
