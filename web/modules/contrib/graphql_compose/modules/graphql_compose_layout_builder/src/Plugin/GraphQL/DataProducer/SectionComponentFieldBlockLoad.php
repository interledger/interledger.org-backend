<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_builder\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Builder section component block loader.
 *
 * @DataProducer(
 *   id = "section_component_field_block_load",
 *   name = @Translation("Layout Builder component block"),
 *   description = @Translation("Get layout builder component block."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Section")
 *   ),
 *   consumes = {
 *     "component" = @ContextDefinition("any",
 *       label = @Translation("Section component"),
 *     ),
 *     "contexts" = @ContextDefinition("any",
 *       label = @Translation("Contexts to apply to the block."),
 *     ),
 *   },
 * )
 */
class SectionComponentFieldBlockLoad extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected ContextHandlerInterface $contextHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->contextHandler = $container->get('context.handler');

    return $instance;
  }

  /**
   * Resolves a block with context for layout builder.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The field the comment references are attached to.
   * @param array $contexts
   *   Contexts available to apply to the block.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(SectionComponent $component, array $contexts, RefinableCacheableDependencyInterface $metadata) {

    /** @var \Drupal\Core\Plugin\ContextAwarePluginInterface $block */
    $block = $component->getPlugin();

    $context_mapping = [];
    foreach ($block->getContextDefinitions() as $context_slot => $definition) {
      $valid_contexts = $this->contextHandler->getMatchingContexts($contexts, $definition);
      foreach (array_keys($valid_contexts) as $context_id) {
        $context_mapping[$context_slot] = $context_id;
      }
    }

    $this->contextHandler->applyContextMapping($block, $contexts, $context_mapping);

    return $block;
  }

}
