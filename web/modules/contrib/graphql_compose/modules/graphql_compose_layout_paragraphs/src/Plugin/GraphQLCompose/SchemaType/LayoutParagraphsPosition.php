<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_paragraphs\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "LayoutParagraphsPosition"
 * )
 */
class LayoutParagraphsPosition extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t("This component positionally belongs to another component's layout."),
      'fields' => fn() => [
        'parentId' => [
          'type' => Type::id(),
          'description' => (string) $this->t('The UUID of the component this component belongs to.'),
        ],
        'region' => [
          'type' => Type::string(),
          'description' => (string) $this->t("There this component is suggested to live within the parent component's regions."),
        ],
      ],
    ]);

    return $types;
  }

}
