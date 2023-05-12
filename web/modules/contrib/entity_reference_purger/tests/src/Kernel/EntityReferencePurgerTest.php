<?php

namespace Drupal\Tests\entity_reference_purger\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the Entity Reference Purger module.
 *
 * @group entity_reference_purger
 */
class EntityReferencePurgerTest extends EntityKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
    'text',
    'system',
    'taxonomy',
    'field',
    'entity_reference_purger_test',
    'entity_reference_purger',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);

    // Create Article content type.
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();

    // Create Tags vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    // Create a term reference field on node.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_tags1',
      'Term reference 1',
      'taxonomy_term',
      'default',
      ['target_bundles' => ['tags']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create a second term reference field on node, and enable removing
    // orphaned entity references.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_tags2',
      'Term reference 2',
      'taxonomy_term',
      'default',
      ['target_bundles' => ['tags']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $field_tags2 = FieldConfig::loadByName('node', 'article', 'field_tags2');
    $field_tags2->setThirdPartySetting('entity_reference_purger', 'remove_orphaned', TRUE);
    $field_tags2->save();
  }

  /**
   * Tests a field where removing orphaned entity references is not enabled.
   */
  public function testWithoutEntityReferencePurger() {
    $term1 = Term::create([
      'vid' => 'tags',
      'name' => 'Apples',
    ]);
    $term1->save();

    $term2 = Term::create([
      'vid' => 'tags',
      'name' => 'Oranges',
    ]);
    $term2->save();

    $term3 = Term::create([
      'vid' => 'tags',
      'name' => 'Strawberries',
    ]);
    $term3->save();

    $term4 = Term::create([
      'vid' => 'tags',
      'name' => 'Apricots',
    ]);
    $term4->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Fruits',
      'field_tags1' => [$term1, $term2],
      'test_base_field1' => [$term3, $term4],
    ]);
    $node->save();

    // Test that we have two referenced terms for the config field.
    $tags = $node->get('field_tags1')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(1, $node->get('field_tags1')->get(0)->target_id);
    $this->assertEquals(2, $node->get('field_tags1')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(4, $terms);

    // Test that we have two referenced terms for the base field.
    $tags = $node->get('test_base_field1')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(3, $node->get('test_base_field1')->get(0)->target_id);
    $this->assertEquals(4, $node->get('test_base_field1')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(4, $terms);

    // Delete the first term and test that we still have two referenced terms
    // for the config field.
    $term1->delete();
    $node = $this->reloadEntity($node);
    $tags = $node->get('field_tags1')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(1, $node->get('field_tags1')->get(0)->target_id);
    $this->assertEquals(2, $node->get('field_tags1')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(3, $terms);

    // Delete the third term and test that we still have two referenced terms
    // for the base field.
    $term3->delete();
    $node = $this->reloadEntity($node);
    $tags = $node->get('test_base_field1')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(3, $node->get('test_base_field1')->get(0)->target_id);
    $this->assertEquals(4, $node->get('test_base_field1')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(2, $terms);
  }

  /**
   * Tests a field where removing orphaned entity references is enabled.
   */
  public function testWithEntityReferencePurger() {
    $term1 = Term::create([
      'vid' => 'tags',
      'name' => 'Apples',
    ]);
    $term1->save();

    $term2 = Term::create([
      'vid' => 'tags',
      'name' => 'Oranges',
    ]);
    $term2->save();

    $term3 = Term::create([
      'vid' => 'tags',
      'name' => 'Strawberries',
    ]);
    $term3->save();

    $term4 = Term::create([
      'vid' => 'tags',
      'name' => 'Apricots',
    ]);
    $term4->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Fruits',
      'field_tags2' => [$term1, $term2],
      'test_base_field2' => [$term3, $term4],
    ]);
    $node->save();

    // Test that we have two referenced terms for the config field.
    $tags = $node->get('field_tags2')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(1, $node->get('field_tags2')->get(0)->target_id);
    $this->assertEquals(2, $node->get('field_tags2')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(4, $terms);

    // Test that we have two referenced terms for the base field.
    $tags = $node->get('test_base_field2')->getValue();
    $this->assertCount(2, $tags);
    $this->assertEquals(3, $node->get('test_base_field2')->get(0)->target_id);
    $this->assertEquals(4, $node->get('test_base_field2')->get(1)->target_id);
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(4, $terms);

    // Delete the first term and test that we now have only one referenced term
    // for the config field.
    $term1->delete();
    $node = $this->reloadEntity($node);
    $tags = $node->get('field_tags2')->getValue();
    $this->assertCount(1, $tags);
    $this->assertEquals(2, $node->get('field_tags2')->get(0)->target_id);
    $this->assertEmpty($node->get('field_tags2')->get(1));
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(3, $terms);

    // Delete the third term and test that we now have only one referenced term
    // for the base field.
    $term3->delete();
    $node = $this->reloadEntity($node);
    $tags = $node->get('test_base_field2')->getValue();
    $this->assertCount(1, $tags);
    $this->assertEquals(4, $node->get('test_base_field2')->get(0)->target_id);
    $this->assertEmpty($node->get('test_base_field2')->get(3));
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(2, $terms);
  }

}
