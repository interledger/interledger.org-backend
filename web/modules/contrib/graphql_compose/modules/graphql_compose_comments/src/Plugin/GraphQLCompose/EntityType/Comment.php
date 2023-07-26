<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;
use Drupal\graphql_compose_comments\CommentableTrait;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeEntityType(
 *   id = "comment",
 *   prefix = "Comment",
 *   edges_producer = "graphql_compose_edges_comments",
 *   edges_query = FALSE,
 *   base_fields = {
 *     "langcode" = {},
 *     "created" = {},
 *     "changed" = {},
 *     "status" = {},
 *     "thread" = {
 *       "required" = FALSE,
 *     },
 *     "subject" = {
 *       "field_type" = "entity_label",
 *       "required" = FALSE,
 *     },
 *     "uid" = {
 *       "field_type" = "comment_author",
 *       "name_sdl" = "author"
 *     }
 *   }
 * )
 */
class Comment extends GraphQLComposeEntityTypeBase {

  use CommentableTrait;

  /**
   * Force add a mutation for comments to the base registerTypes.
   */
  public function registerTypes(): void {
    parent::registerTypes();

    foreach ($this->getBundles() as $bundle) {

      if (!$bundle->getSetting('comments_mutation_enabled')) {
        continue;
      }

      $input_name = $this->getMutationInputNameSdl($bundle);
      $mutation_name = $this->getMutationNameSdl($bundle);

      /*
       * Add a unique mutation per comment-type.
       */
      $mutation_type = new ObjectType([
        'name' => 'Mutation',
        'fields' => fn() => [
          $mutation_name => [
            'type' => $this->gqlSchemaTypeManager->get($bundle->getTypeSdl()),
            'description' => (string) $this->t('Add a comment.'),
            'args' => [
              'data' => [
                'type' => Type::nonNull($this->gqlSchemaTypeManager->get($input_name)),
                'description' => (string) $this->t('Comment content'),
              ],
            ],
          ],
        ],
      ]);

      $this->gqlSchemaTypeManager->extend($mutation_type);

      /*
       * Add a unique input type.
       */
      $input_type = new InputObjectType([
        'name' => $input_name,
        'description' => (string) $this->t('Comment for comment type @id', [
          '@id' => $bundle->getTypeSdl(),
        ]),
        'fields' => function () use ($bundle) {

          // Standard input fields.
          $fields = [
            'entityType' => [
              'type' => Type::nonNull($this->gqlSchemaTypeManager->get('CommentAvailable')),
              'description' => (string) $this->t('The type of entity we are adding a comment to.'),
            ],
            'entityId' => [
              'type' => Type::nonNull(Type::id()),
              'description' => (string) $this->t('The ID of the entity we are adding a comment to.'),
            ],
            'entityField' => [
              'type' => Type::string(),
              'description' => (string) $this->t('The field name that contains comments. Supply if multiple comment fields are available.'),
            ],
            'replyTo' => [
              'type' => Type::id(),
              'description' => (string) $this->t('The ID of the comment we are replying to.'),
            ],
            'authorName' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Comment author name.'),
            ],
            'authorEmail' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Comment author email.'),
            ],
            'authorHomepage' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Comment author homepage URL.'),
            ],
          ];

          // Add unique fields for comment type.
          $fields += $this->getInputFields($this->getPluginId(), $bundle->entity->id());

          return $fields;
        },
      ]);

      $this->gqlSchemaTypeManager->add($input_type);

    }
  }

}
