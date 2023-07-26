<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Wrapper\EntityTypeWrapper;

/**
 * Defines a entity type plugin that returns a entity type part.
 */
interface GraphQLComposeEntityTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Description of this entity type.
   *
   * @return string|null
   *   Description of this entity type.
   */
  public function getDescription(): ?string;

  /**
   * Prefix for this entity type. Eg Paragraph.
   *
   * @return string
   *   Prefix for this entity type. Eg Paragraph.
   */
  public function getPrefix(): string;

  /**
   * Get bundles enabled for this entity type.
   *
   * @return \Drupal\graphql_compose\Wrapper\EntityTypeWrapper[]
   *   Enabled bundles for plugin.
   */
  public function getBundles(): array;

  /**
   * Get single bundle by bundle id, enabled for this entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundle(string $bundle_id): ?EntityTypeWrapper;

  /**
   * Entity wide type registration.
   */
  public function registerTypes(): void;

  /**
   * Allow type plugins to add extra resolvers.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void;

  /**
   * Get common union name between entity bundles.
   *
   * @return string
   *   Common union name between entity bundles.
   */
  public function getUnionTypeSdl(): string;

}
