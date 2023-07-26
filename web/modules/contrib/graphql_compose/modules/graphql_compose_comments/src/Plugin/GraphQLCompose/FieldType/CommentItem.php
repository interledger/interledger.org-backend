<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "comment"
 * )
 */
class CommentItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   *
   * If user can't access comments, then it can't be required in the schema.
   * Clearer to just return FALSE and let permissions do the rest.
   */
  public function isRequired(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSdl(): string {

    $target_type_id = 'comment';

    // Entity type not defined.
    if (!$this->entityTypeManager->getDefinition('comment', FALSE)) {
      return 'UnsupportedType';
    }

    // Entity type plugin not defined.
    if (!$plugin_instance = $this->gqlEntityTypeManager->getPluginInstance($target_type_id)) {
      return 'UnsupportedType';
    }

    // No enabled bundles.
    if (empty($plugin_instance->getBundles())) {
      return 'UnsupportedType';
    }

    // No setting on field definition.
    if (!$comment_type = $this->getFieldDefinition()->getFieldStorageDefinition()->getSetting('comment_type')) {
      return 'UnsupportedType';
    }

    // Comment type not enabled.
    if (!$bundle = $plugin_instance->getBundle($comment_type)) {
      return 'UnsupportedType';
    }

    return $bundle->getTypeSdl() . 'Connection';
  }

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    return $builder->compose(
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue($this->getFieldName())),

      $builder->produce('graphql_compose_edges_comments')
        ->map('field_list', $builder->fromParent())
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgsSdl(): array {

    $manager = $this->gqlSchemaTypeManager;

    return [
      'after' => [
        'type' => $manager->get('Cursor'),
        'description' => (string) $this->t('Returns the elements that come after the specified cursor.'),
      ],
      'before' => [
        'type' => $manager->get('Cursor'),
        'description' => (string) $this->t('Returns the elements that come before the specified cursor.'),
      ],
      'first' => [
        'type' => Type::int(),
        'description' => (string) $this->t('Returns up to the first n elements from the list.'),
      ],
      'last' => [
        'type' => Type::int(),
        'description' => (string) $this->t('Returns up to the last n elements from the list.'),
      ],
      'reverse' => [
        'type' => Type::boolean(),
        'defaultValue' => FALSE,
        'description' => (string) $this->t('Reverse the order of the underlying list.'),
      ],
    ];
  }

}
