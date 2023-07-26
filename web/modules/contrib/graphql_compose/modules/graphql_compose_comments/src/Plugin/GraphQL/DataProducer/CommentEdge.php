<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQL\DataProducer;

use Drupal\comment\CommentFieldItemList;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_comments\CommentQueryHelper;
use Drupal\graphql_compose_edges\EntityConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queries entities on the platform.
 *
 * @DataProducer(
 *   id = "graphql_compose_edges_comments",
 *   name = @Translation("Query a list of entity type"),
 *   description = @Translation("Loads the entity type entities."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("EntityConnection")
 *   ),
 *   consumes = {
 *     "field_list" = @ContextDefinition("any",
 *       label = @Translation("Field the comment references are attached to"),
 *     ),
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
 *   },
 * )
 */
class CommentEdge extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected EntityBuffer $graphqlEntityBuffer;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->graphqlEntityBuffer = $container->get('graphql.buffer.entity');
    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * Resolves the request to the requested values.
   *
   * @param \Drupal\comment\CommentFieldItemList|null $field_list
   *   The field the comment references are attached to.
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
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return \Drupal\graphql_compose\GraphQL\ConnectionInterface
   *   An entity connection with results and data about the paginated results.
   */
  public function resolve(?CommentFieldItemList $field_list, ?int $first, ?string $after, ?int $last, ?string $before, ?bool $reverse, RefinableCacheableDependencyInterface $metadata) {

    // If access was denied to the field, $field_list will be null.
    if (!$field_list) {
      return NULL;
    }

    $query_helper = new CommentQueryHelper(
      $field_list,
      $this->entityTypeManager,
      $this->graphqlEntityBuffer,
    );

    $connection = new EntityConnection($query_helper);
    $connection->setPagination($first, $after, $last, $before, $reverse);
    $connection->setAccessAccount($this->currentUser);
    $connection->setCacheContext($metadata);

    return $connection;
  }

}
