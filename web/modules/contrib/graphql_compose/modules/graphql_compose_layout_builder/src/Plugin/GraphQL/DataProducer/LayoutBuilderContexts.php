<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_layout_builder\EnabledBundlesTrait;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Builder section contexts.
 *
 * @DataProducer(
 *   id = "layout_builder_contexts",
 *   name = @Translation("Layout Builder contexts"),
 *   description = @Translation("Get layout builder contexts."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Section")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("any",
 *       label = @Translation("Entity with layouts enabled"),
 *     ),
 *     "view_mode" = @ContextDefinition("string",
 *       label = @Translation("View mode name"),
 *       required = FALSE,
 *     ),
 *   },
 * )
 */
class LayoutBuilderContexts extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use EnabledBundlesTrait;

  /**
   * The layout builder section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected SectionStorageManagerInterface $sectionStorageManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected ContextRepositoryInterface $contextRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->sectionStorageManager = $container->get('plugin.manager.layout_builder.section_storage');
    $instance->contextRepository = $container->get('context.repository');

    return $instance;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The field the comment references are attached to.
   * @param string|null $view_mode
   *   The view mode to load.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return array|null
   *   An array of contexts for the requested entity and view_mode.
   */
  public function resolve(?EntityInterface $entity, ?string $view_mode, RefinableCacheableDependencyInterface $metadata) {

    // If access was denied to the field, $field_list will be null.
    if (!$entity) {
      return NULL;
    }

    $metadata->addCacheableDependency($entity);

    // Check if view entity is enabled.
    $view_modes_enabled = $this->getLayoutBuilderViewDisplays(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    $view_mode = $view_mode ?: 'default';
    $view_mode = array_key_exists($view_mode, $view_modes_enabled) ? $view_mode : 'default';

    $metadata->addCacheableDependency($view_modes_enabled[$view_mode]);

    $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);

    $available_context_ids = array_keys($this->contextRepository->getAvailableContexts());

    $context = $this->contextRepository->getRuntimeContexts($available_context_ids) + [
      'view_mode' => new Context(ContextDefinition::create('string'), $display->getMode()),
      'entity' => EntityContext::fromEntity($entity),
      'display' => EntityContext::fromEntity($display),
    ];

    return $context;
  }

}
