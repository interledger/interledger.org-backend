<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_layout_paragraphs\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Get the Paragraph if it has Layout Paragraphs settings. Stupid code.
 *
 * @DataProducer(
 *   id = "layout_paragraphs",
 *   name = @Translation("Layout Paragraphs"),
 *   description = @Translation("Layout paragraphs information for Paragraph entity."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Layout Paragraphs settings")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity:paragraph",
 *       label = @Translation("Paragraph")
 *     )
 *   }
 * )
 */
class LayoutParagraphs extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Return the entity if it has layout paragraphs setting.
   *
   * @param \Drupal\paragraphs\ParagraphInterface|null $entity
   *   The paragraph entity.
   *
   * @return array|null
   *   The layout paragraphs settings.
   */
  public function resolve(?ParagraphInterface $entity): ?array {
    if (!$entity) {
      return NULL;
    }

    $settings = $entity->getAllBehaviorSettings();
    $settings = $settings['layout_paragraphs'] ?? [];

    $result = [];
    if (!empty($settings['parent_uuid'])) {
      $result['position'] = [
        'parentId' => $settings['parent_uuid'],
        'region' => $settings['region'] ?? NULL,
      ];
    }

    if (array_key_exists('layout', $settings)) {
      $result['layout'] = $settings['layout'];
    }

    return $result;
  }

}
