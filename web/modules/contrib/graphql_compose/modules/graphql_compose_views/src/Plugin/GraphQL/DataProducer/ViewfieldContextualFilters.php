<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get contextual filters from a view field with token replacement.
 *
 * @DataProducer(
 *   id = "viewfield_contextual_filters",
 *   name = @Translation("Views contextual filters"),
 *   description = @Translation("Contextual filters from a view field with token replacement"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Contextual filters")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("any",
 *       label = @Translation("Field entity")
 *     ),
 *     "filters" = @ContextDefinition("any",
 *       label = @Translation("View contextual filters")
 *     )
 *   }
 * )
 */
class ViewfieldContextualFilters extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal token manager.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->token = $container->get('token');
    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * Resolve viewfield contextual filters.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param array $filters
   *   The contextual filters.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cache metadata.
   *
   * @return array|null
   *   Contextual filters with token processing.
   */
  public function resolve(EntityInterface $entity, array $filters, RefinableCacheableDependencyInterface $metadata): ?array {

    $bubbleable = new BubbleableMetadata();
    $render_context = new RenderContext();

    $data = [
      $entity->getEntityTypeId() => $entity,
    ];

    $results = $this->renderer->executeInRenderContext(
      $render_context,
      fn () => array_map(function ($filter) use ($data, $bubbleable) {
        return is_string($filter)
          ? $this->token->replace($filter, $data, [], $bubbleable)
          : $filter;
      }, $filters)
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    $metadata->addCacheableDependency($entity);

    return $results ?: NULL;
  }

}
