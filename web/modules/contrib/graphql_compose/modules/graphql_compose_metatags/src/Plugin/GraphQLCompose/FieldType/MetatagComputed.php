<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_metatags\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemsInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\typed_data\DataFetcherTrait;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "metatag_computed",
 *   type_sdl = "MetaTagUnion",
 * )
 */
class MetatagComputed extends GraphQLComposeFieldTypeBase implements FieldProducerItemsInterface {

  use TypedDataTrait;
  use DataFetcherTrait;
  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItems(FieldItemListInterface $field, array $context, RefinableCacheableDependencyInterface $metadata): array {

    $bubbleable = new BubbleableMetadata();
    $render_context = new RenderContext();

    $manager = $this->getTypedDataManager();

    $type = 'entity:' . $field->getFieldDefinition()->getTargetEntityTypeId();
    $definition = $manager->createDataDefinition($type);
    $typed_data = $manager->create($definition, $field->getEntity());

    $result = \Drupal::service('renderer')->executeInRenderContext(
      $render_context,
      function () use ($typed_data, $bubbleable) {
        return $this->getDataFetcher()->fetchDataByPropertyPath($typed_data, 'metatag', $bubbleable)->getValue();
      }
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    return $result ?: [];
  }

}
