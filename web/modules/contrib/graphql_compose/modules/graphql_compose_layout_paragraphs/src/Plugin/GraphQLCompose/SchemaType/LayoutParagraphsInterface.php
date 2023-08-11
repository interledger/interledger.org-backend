<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_paragraphs\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "LayoutParagraphsInterface",
 * )
 */
class LayoutParagraphsInterface extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('This content has been arranged by a User using Layout Paragraphs.'),
      'fields' => fn() => [
        'composition' => Type::nonNull(static::type('LayoutParagraphs')),
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $paragraph_plugin = $this->gqlEntityTypeManager->getPluginInstance('paragraph');

    foreach ($paragraph_plugin->getBundles() as $bundle) {

      $extensions[] = new ObjectType([
        'name' => $bundle->getTypeSdl(),
        'interfaces' => fn() => [
          static::type('LayoutParagraphsInterface'),
        ],
        'fields' => fn() => [
          'composition' => [
            'type' => Type::nonNull(static::type('LayoutParagraphs')),
            'description' => (string) $this->t('Layout Paragraphs metadata for this paragraph.'),
          ],
        ],
      ]);
    }

    return $extensions;
  }

}
