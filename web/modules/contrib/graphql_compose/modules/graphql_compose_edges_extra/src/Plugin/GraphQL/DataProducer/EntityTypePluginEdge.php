<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_edges_extra\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityUuidBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_edges_extra\EntityConnection;
use Drupal\graphql_compose_edges_extra\EntityTypePluginQueryHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries entities on the platform.
 *
 * @DataProducer(
 *   id = "graphql_compose_edges_entity_type",
 *   name = @Translation("Query a list of entity type"),
 *   description = @Translation("Loads the entity type entities."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "first" = @ContextDefinition("integer",
 *       label = @Translation("First"),
 *       required = FALSE
 *     ),
 *     "after" = @ContextDefinition("string",
 *       label = @Translation("After"),
 *       required = FALSE
 *     ),
 *     "last" = @ContextDefinition("integer",
 *       label = @Translation("Last"),
 *       required = FALSE
 *     ),
 *     "before" = @ContextDefinition("string",
 *       label = @Translation("Before"),
 *       required = FALSE
 *     ),
 *     "reverse" = @ContextDefinition("boolean",
 *       label = @Translation("Reverse"),
 *       required = FALSE,
 *     ),
 *     "sortKey" = @ContextDefinition("string",
 *       label = @Translation("Sort key"),
 *       required = FALSE
 *     ),
 *     "filter" = @ContextDefinition("any",
 *       label = @Translation("Filter"),
 *       required = FALSE
 *     ),
 *   },
 *   deriver = "Drupal\graphql_compose_edges_extra\Plugin\Derivative\QueryEntityTypePluginTypeDeriver"
 * )
 */
class EntityTypePluginEdge extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
   * @param int|null $first
   *   Fetch the first X results.
   * @param string|null $after
   *   Cursor to fetch results after.
   * @param int|null $last
   *   Fetch the last X results.
   * @param string|null $before
   *   Cursor to fetch results before.
   * @param bool|null $reverse
   *   Reverses the order of the data.
   * @param string|null $sortKey
   *   Key to sort by.
   * @param mixed $filter
   *   Query filter.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(?int $first, ?string $after, ?int $last, ?string $before, bool $reverse, ?string $sortKey, mixed $filter, RefinableCacheableDependencyInterface $metadata)
  {

    [$entityType, $entityBundle] = explode(':', $this->getDerivativeId());

    $query_helper = new EntityTypePluginQueryHelper(
      $sortKey,
      $filter,
      $this->entityTypeManager,
      $this->graphqlEntityBuffer,
      $entityType,
      $entityBundle
    );

    $metadata->addCacheableDependency($query_helper);

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    return $connection;
  }

}
