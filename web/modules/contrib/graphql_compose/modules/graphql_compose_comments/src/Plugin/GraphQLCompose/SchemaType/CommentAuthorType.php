<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_comments\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "CommentAuthor",
 * )
 */
class CommentAuthorType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Comment author.'),
      'fields' => function () {

        $fields = [
          'name' => [
            'type' => Type::string(),
            'description' => (string) $this->t('Comment author name.'),
          ],
          'email' => [
            'type' => static::type('Email'),
            'description' => (string) $this->t('Comment author email.'),
          ],
          'homepage' => [
            'type' => Type::string(),
            'description' => (string) $this->t('Comment author homepage URL.'),
          ],
        ];

        if ($this->moduleHandler->moduleExists('graphql_compose_users')) {
          $fields['user'] = [
            'type' => static::type('User'),
            'description' => (string) $this->t('If the comment owner has an account this will be filled.'),
          ];
        }

        return $fields;
      },
    ]);

    return $types;
  }

}
