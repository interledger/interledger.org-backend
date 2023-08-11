<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments;

use Drupal\comment\CommentFieldItemList;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql_compose_edges\EntityTypePluginQueryHelper;

/**
 * Load comments for entity.
 */
class CommentQueryHelper extends EntityTypePluginQueryHelper {

  /**
   * Sort by created time always.
   *
   * @var string|null
   */
  protected ?string $sortKey = 'CREATED_AT';

  /**
   * Language code.
   *
   * @var string|null
   */
  protected ?string $langcode = NULL;

  /**
   * The entity type.
   *
   * @var string
   */
  protected string $entityTypeId = 'comment';

  /**
   * Entity bundle (set per field attachment).
   *
   * @var string
   */
  protected string $entityBundleId;

  /**
   * Create a new connection query helper.
   *
   * @param \Drupal\comment\CommentFieldItemList $fieldList
   *   The field this comment is attached to.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $graphqlEntityBuffer
   *   The GraphQL entity buffer.
   */
  public function __construct(
    protected CommentFieldItemList $fieldList,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityBuffer $graphqlEntityBuffer,
  ) {
    $this->entityBundleId = $fieldList
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSetting('comment_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {

    // Standard query object.
    $query = parent::getQuery();

    $field_entity = $this->fieldList->getEntity();
    $field_definition = $this->fieldList->getFieldDefinition();

    // Limit to the parent entity.
    $query->condition('entity_type', $field_entity->getEntityTypeId());
    $query->condition('entity_id', $field_entity->id());
    $query->condition('field_name', $field_definition->getName());

    return $query;
  }

}
