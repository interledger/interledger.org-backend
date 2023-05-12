<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\user\Entity\User;

/**
 * Tests metatag field serialization.
 *
 * @group metatag
 */
class MetatagSerializationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    'serialization',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->serializer = \Drupal::service('serializer');

    // Create a generic metatag field.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'metatag',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();

    $this->installConfig(['system', 'metatag']);
    // Ensure a site name.
    $this->config('system.site')->set('name', 'Test site')->save();

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->getEditable('metatag.metatag_defaults.global')
      ->set('tags.title', 'Global Title')
      ->set('tags.description', 'Global description')
      ->set('tags.keywords', 'drupal8, testing, jsonapi, metatag')
      ->save();

    MetatagDefaults::create([
      'id' => 'entity_test',
      'tags' => [
        'title' => '[entity_test:name] | [site:name]',
        'canonical_url' => '[entity_test:url]',
      ],
    ])->save();
  }

  /**
   * Tests the deserialization.
   */
  public function testMetatagDeserialization() {
    $entity = EntityTest::create();
    $json = json_decode($this->serializer->serialize($entity, 'json'), TRUE);
    $json['field_test'][0]['value'] = 'string data';
    $serialized = json_encode($json, TRUE);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "value" properties of the "field_test" field (field item class: Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItem).');
    $this->serializer->deserialize($serialized, EntityTest::class, 'json');
  }

  /**
   * Tests normalization of the computed metatag field.
   */
  public function testJsonapiNormalization() {
    $this->enableModules(['jsonapi']);
    $serializer = $this->container->get('jsonapi.serializer');
    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');

    $entity = EntityTest::create([
      'name' => 'Llama',
      'type' => 'entity_test',
      'field_test' => [
        'value' => [
          'description' => 'This is a description for use in Search Engines',
        ],
      ],
    ]);
    assert($entity instanceof EntityTest);
    // Validation initializes computed fields, verify this doesn't create a set
    // of problematic field values.
    $entity->validate();
    $entity->save();


    $resource_type = $resource_type_repository->get($entity->getEntityTypeId(), $entity->bundle());
    $resource_object = ResourceObject::createFromEntity($resource_type, $entity);
    $cacheable_normalization = $serializer->normalize($resource_object, 'api_json', [
      'resource_type' => $resource_type,
      'account' => User::getAnonymousUser(),
    ]);
    assert($cacheable_normalization instanceof CacheableNormalization);
    $normalization = $cacheable_normalization->getNormalization();
    assert(is_array($normalization));
    $this->assertEquals([
      [
        'tag' => 'meta',
        'attributes' => [
          'name' => 'title',
          'content' => 'Llama | Test site',
        ],
      ],
      [
        'tag' => 'link',
        'attributes' => [
          'rel' => 'canonical',
          'href' => $entity->toUrl()->toString(),
        ],
      ],
      [
        'tag' => 'meta',
        'attributes' => [
          'name' => 'description',
          'content' => 'This is a description for use in Search Engines',
        ],
      ],
      [
        'tag' => 'meta',
        'attributes' => [
          'name' => 'keywords',
          'content' => 'drupal8, testing, jsonapi, metatag',
        ],
      ],
    ], $normalization['attributes']['metatag'], var_export($normalization['attributes']['metatag'], TRUE));
  }

}
