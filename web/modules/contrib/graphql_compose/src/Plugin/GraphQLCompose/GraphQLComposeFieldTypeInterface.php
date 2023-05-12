<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
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
   *   The Resolver Builder.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\Composite
   *   A composite for the resolver registry.
   */
  public function getProducers(ResolverBuilder $builder): Composite;

  /**
   * Get field definition for this field plugin instance.
   */
  public function getFieldDefinition(): ?FieldDefinitionInterface;

  /**
   * Field type configuration.
   */
  public function getFieldType(): string;

  /**
   * Field name configuration.
   */
  public function getFieldName(): string;

  /**
   * Get the wrapped entity type assigned to this field.
   */
  public function getEntityWrapper(): ?EntityTypeWrapper;

  /**
   * Description of this field type.
   */
  public function getDescription(): ?string;

  /**
   * Plugin field is multiple.
   */
  public function isMultiple(): bool;

  /**
   * Plugin field is required.
   */
  public function isRequired(): bool;

  /**
   * The GraphQL name for this field.
   */
  public function getNameSdl(): string;

  /**
   * The GraphQL type for this field.
   */
  public function getTypeSdl(): string;

  /**
   * Entity wide SDL.
   */
  public function registerTypes(): void;

}
