<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests specific to GraphQL Compose entity type: Taxonomy.
 *
 * @group graphql_compose
 */
class EntityTaxonomyTest extends GraphQLComposeBrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * The test vocab.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $vocabulary;

  /**
   * The test term.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected array $terms;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = Vocabulary::create([
      'name' => 'Test',
      'vid' => 'test',
    ]);

    $this->vocabulary->save();

    $this->terms[1] = $this->createTerm($this->vocabulary, [
      'name' => 'Test term A',
    ]);

    $this->terms[2] = $this->createTerm($this->vocabulary, [
      'name' => 'Test term B',
    ]);

    $this->terms[3] = $this->createTerm($this->vocabulary, [
      'name' => 'Test term A A',
      'parent' => $this->terms[1]->id(),
    ]);

    $this->setEntityConfig('taxonomy_term', 'test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by id.
   */
  public function testTermLoadByUuid(): void {
    $query = <<<GQL
      query {
        termTest(id: "{$this->terms[2]->uuid()}") {
          id
          name
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $term = $content['data']['termTest'];

    $this->assertEquals($this->terms[2]->uuid(), $term['id']);
    $this->assertEquals('Test term B', $term['name']);
  }

  /**
   * Test taxonomy term parents.
   */
  public function testTermParents(): void {

    // Test expected parent.
    $query = <<<GQL
      query {
        termTest(id: "{$this->terms[3]->uuid()}") {
          id
          parent {
            ... on TermTest {
              id
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $term = $content['data']['termTest'];

    $this->assertEquals($this->terms[3]->uuid(), $term['id']);
    $this->assertEquals($this->terms[1]->uuid(), $term['parent']['id']);

    // Test empty.
    $query = <<<GQL
      query {
        termTest(id: "{$this->terms[2]->uuid()}") {
          id
          parent {
            ... on TermTest {
              id
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $term = $content['data']['termTest'];

    $this->assertEquals($this->terms[2]->uuid(), $term['id']);
    $this->assertNull($term['parent']);
  }

}
