<?php

namespace Drupal\graphql_compose_statistics\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @DataProducer(
 *   id = "graphql_compose_statistics_record_view",
 *   name = @Translation("Record a node view"),
 *   description = @Translation("Record a node view."),
 *   produces = @ContextDefinition("boolean",
 *     label = @Translation("Success")
 *   ),
 *   consumes = {
 *     "id" = @ContextDefinition("string",
 *       label = @Translation("id"),
 *       required = TRUE
 *     )
 *   },
 * )
 */
class StatisticsRecordView extends DataProducerPluginBase implements ContainerFactoryPluginInterface
{

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity'),
      $container->get('graphql.buffer.entity_uuid'),
      $container->get('graphql.buffer.entity_revision')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer $graphqlEntityUuidBuffer
   *   The GraphQL entity uuid buffer.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $graphqlEntityRevisionBuffer
   *   The GraphQL entity revision buffer.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $graphqlEntityBuffer,
    protected EntityUuidBuffer $graphqlEntityUuidBuffer,
    protected EntityRevisionBuffer $graphqlEntityRevisionBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->graphqlEntityBuffer = $graphqlEntityBuffer;
    $this->graphqlEntityUuidBuffer = $graphqlEntityUuidBuffer;
    $this->graphqlEntityRevisionBuffer = $graphqlEntityRevisionBuffer;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param string $id
   *   Node ID.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(string $id, RefinableCacheableDependencyInterface $metadata)
  {


    $entity = \Drupal::service('entity.repository')->loadEntityByUuid('node', $id);

    if ($entity) {
      $metadata->addCacheableDependency($entity);
      $context = new RenderContext();
      \Drupal::service('renderer')->executeInRenderContext(
        $context,
        function () use ($entity) {
          $current_value = $entity->get('field_node_view_count')->getValue()[0]['value'];
          $new_value = ++$current_value;
          $entity->set('field_node_view_count',$new_value);
          $entity->save();
        }
      );
      return ['success' => TRUE];
    }

    return ['success' => FALSE];
  }
}
