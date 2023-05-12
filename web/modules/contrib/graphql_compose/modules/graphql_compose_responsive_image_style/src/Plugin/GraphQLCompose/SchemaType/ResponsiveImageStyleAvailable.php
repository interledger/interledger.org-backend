<?php

namespace Drupal\graphql_compose_responsive_image_style\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use GraphQL\Type\Definition\EnumType;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "ResponsiveImageStyleAvailable"
 * )
 */
class ResponsiveImageStyleAvailable extends GraphQLComposeSchemaTypeBase
{

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array
  {
    $types = [];

    $config = $this->configFactory->get('graphql_compose.settings');

    $responsive_image_styles = array_filter(
      $this->entityTypeManager->getStorage('responsive_image_style')->loadMultiple(),
      fn (ResponsiveImageStyleInterface $style) => $config->get('responsive_image_style.' . $style->id() . '.enabled') ?: FALSE
    );

    $values = [];
    foreach ($responsive_image_styles as $responsive_image_style) {
      $id = str_replace('-', '_', mb_strtoupper($responsive_image_style->id()));
      $id = preg_replace('/[^A-Za-z0-9\\-_]/', '', $id);
      $values[$id] = [
        'value' => $responsive_image_style->id(),
        'description' => (string) $responsive_image_style->label(),
      ];
    }

    $undefined = [
      'UNDEFINED' => [
        'value' => 'undefined',
        'description' => (string) $this->t('No responsive image styles have been enabled.'),
      ],
    ];

    $types[] = new EnumType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('List of responsive image styles available to use.'),
      'values' => $values ?: $undefined,
    ]);

    return $types;
  }
}
