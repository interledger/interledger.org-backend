<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\image\ImageStyleInterface;
use GraphQL\Type\Definition\EnumType;

use function Symfony\Component\String\u;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ImageStyleAvailable"
 * )
 */
class ImageStyleAvailable extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $settings = $this->configFactory->get('graphql_compose.settings');

    $image_styles = array_filter(
      $this->entityTypeManager->getStorage('image_style')->loadMultiple(),
      fn (ImageStyleInterface $style) => $settings->get('entity_config.image_style.' . $style->id() . '.enabled') ?: FALSE
    );

    $values = [];
    foreach ($image_styles as $image_style) {
      $id = u($image_style->id())->snake()->upper()->toString();

      $values[$id] = [
        'value' => $image_style->id(),
        'description' => (string) $image_style->label(),
      ];
    }

    $undefined = [
      'UNDEFINED' => [
        'value' => 'undefined',
        'description' => (string) $this->t('No image styles have been enabled.'),
      ],
    ];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('List of image styles available to use.'),
      'values' => $values ?: $undefined,
    ]);

    return $types;
  }

}
