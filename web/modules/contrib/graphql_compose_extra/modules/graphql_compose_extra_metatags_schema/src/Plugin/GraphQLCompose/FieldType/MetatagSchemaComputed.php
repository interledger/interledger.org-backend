<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_extra_metatags_schema\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemsInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\schema_metatag\SchemaMetatagManager;
use Drupal\typed_data\DataFetcherTrait;

/**
 * {@inheritDoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "metatag_computed",
 *   type_sdl = "MetaTagUnion",
 * )
 */
class MetatagSchemaComputed extends GraphQLComposeFieldTypeBase implements FieldProducerItemsInterface
{

  use TypedDataTrait;
  use DataFetcherTrait;
  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItems(FieldItemListInterface $field, array $context, RefinableCacheableDependencyInterface $metadata): array
  {

    $bubbleable = new BubbleableMetadata();
    $render_context = new RenderContext();

    $manager = $this->getTypedDataManager();

    $type = 'entity:' . $field->getFieldDefinition()->getTargetEntityTypeId();
    $definition = $manager->createDataDefinition($type);
    $typed_data = $manager->create($definition, $field->getEntity());

    $entity = $field->getEntity();





    $result = \Drupal::service('renderer')->executeInRenderContext(
      $render_context,
      function () use ($typed_data, $bubbleable, $entity) {
        $allTags = $this->getDataFetcher()->fetchDataByPropertyPath($typed_data, 'metatag', $bubbleable)->getValue();
        $filteredTags = array_values(array_filter($allTags, function ($tag) {
          return !isset($tag['attributes']['schema_metatag']);
        }));
        $metatag_manager = \Drupal::service('metatag.manager');
        $tags = $metatag_manager->tagsFromEntityWithDefaults($entity);
        $elements = $metatag_manager->generateElements($tags, $entity);

        $jsonLd = SchemaMetatagManager::parseJsonld(
          $elements['#attached']['html_head']
        );
        if ($jsonLd) {
          $json = json_encode($jsonLd);
          $schemaTag = array(
            'tag' => 'meta',
            'attributes' => array('jsonld' => $json)
          );
          $filteredTags[] = $schemaTag;
        }
        return $filteredTags;
      }
    );

    if (!$render_context->isEmpty()) {
      $metadata->addCacheableDependency($render_context->pop());
    }

    return $result ?: [];
  }
}
