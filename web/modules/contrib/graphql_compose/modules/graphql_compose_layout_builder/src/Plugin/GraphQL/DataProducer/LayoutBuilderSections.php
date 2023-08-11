<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_layout_builder\Wrapper\LayoutBuilderSection;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Builder section loader.
 *
 * @DataProducer(
 *   id = "layout_builder_sections",
 *   name = @Translation("Layout Builder sections"),
 *   description = @Translation("Get layout builder sections."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Section")
 *   ),
 *   consumes = {
 *     "contexts" = @ContextDefinition("any",
 *       label = @Translation("Layout builder contexts"),
 *     ),
 *   },
 * )
 */
class LayoutBuilderSections extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The layout builder section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected SectionStorageManagerInterface $sectionStorageManager;

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

    return $instance;
  }

  /**
   * Return sections for a layout builder context.
   *
   * @param array|null $contexts
   *   The contexts to use to load the sections.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return Section[]|null
   *   Sections loaded by context.
   */
  public function resolve(?array $contexts, RefinableCacheableDependencyInterface $metadata) {

    if (is_null($contexts)) {
      return NULL;
    }

    $section_storage = $this->sectionStorageManager->findByContext($contexts, $metadata);
    if (!$section_storage) {
      return NULL;
    }

    $metadata->addCacheableDependency($section_storage);

    $sections = [];
    foreach ($section_storage->getSections() as $delta => $section) {
      $sections[] = new LayoutBuilderSection($section, $section_storage, $delta);
    }

    return $sections;
  }

}
