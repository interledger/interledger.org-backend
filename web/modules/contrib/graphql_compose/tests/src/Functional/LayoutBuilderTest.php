<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\Tests\layout_builder\FunctionalJavascript\LayoutBuilderSortTrait;

/**
 * Layout builder tests.
 *
 * @group graphql_compose
 */
class LayoutBuilderTest extends GraphQLComposeBrowserTestBase {

  use LayoutBuilderSortTrait;

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * Block content to place.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected BlockContentInterface $blockContent;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'layout_builder',
    'contextual',
    'graphql_compose_layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a block type to place.
    $block_type = BlockContentType::create([
      'id' => 'basic_block',
      'label' => 'Basic block',
    ]);
    $block_type->save();

    block_content_add_body_field($block_type->id());

    $this->blockContent = BlockContent::create([
      'info' => 'My content block',
      'type' => 'basic_block',
      'body' => [
        [
          'value' => 'This is the block content',
          'format' => filter_default_format(),
        ],
      ],
    ]);

    $this->blockContent->save();

    // Create the node type.
    $this->createContentType(['type' => 'test']);
    $this->createContentType(['type' => 'another_type']);

    // Enable layout builder (default).
    LayoutBuilderEntityViewDisplay::load('node.test.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Create the node.
    $this->node = $this->createNode([
      'type' => 'test',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
          'format' => 'plain_text',
        ],
      ],
    ]);

    // Configure the layout.
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'bypass node access',
      'create test content',
      'edit any test content',
      'create basic_block block content',
      'edit any basic_block block content',
      'create and edit custom blocks',
    ]));

    $page = $this->getSession()->getPage();

    $this->drupalGet('node/' . $this->node->id() . '/layout');

    // Add a new section.
    $this->clickLink('Add section');
    $this->clickLink('Three column');
    $page->fillField('layout_settings[label]', 'Top section');
    $page->selectFieldOption('layout_settings[column_widths]', '25-25-50');
    $page->pressButton('Add section');

    // Region first, Left content, plugin block.
    $page->find('css', '.layout--threecol-section[data-layout-delta="0"] > div:nth-child(1)')->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');

    // Region third, Right content, inline block content.
    $page->find('css', '.layout--threecol-section[data-layout-delta="0"] > div:nth-child(3)')->clickLink('Add block');
    $page->clickLink('Create content block');
    $page->fillField('settings[label]', 'My inline block');
    $page->fillField('settings[block_form][body][0][value]', 'My inline block content');
    $page->uncheckField('settings[label_display]');
    $page->pressButton('Add block');

    // Region second. Middle content, basic block content
    // (Added last to mess with the order).
    $page->find('css', '.layout--threecol-section[data-layout-delta="0"] > div:nth-child(2)')->clickLink('Add block');
    $page->clickLink('My content block');
    $page->fillField('settings[label]', 'My test block');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');

    // Ensure the body is on there already.
    $page->find('css', '.field--name-body');

    // Add the title field (Test for interface field)
    // Region second. Middle content, basic block content
    // (Added last to mess with the order).
    $page->find('css', '.layout--onecol')->clickLink('Add block');
    $page->clickLink('Title');
    $page->uncheckField('settings[label_display]');
    $page->selectFieldOption('settings[formatter][label]', 'hidden');
    $page->uncheckField('settings[formatter][settings][link_to_entity]');
    $page->pressButton('Add block');

    $page->pressButton('Save layout');

    // Reload node entity.
    $this->node = Node::load($this->node->id());

    $this->drupalLogout();

    // Setup GraphQL Compose.
    // Enable node.
    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
      'layout_builder_enabled' => TRUE,
    ]);

    $this->setFieldConfig('node', 'test', 'body', [
      'enabled' => TRUE,
    ]);

    // Enable block type.
    $this->setEntityConfig('block_content', 'basic_block', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $this->setFieldConfig('block_content', 'basic_block', 'body', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Ensure the layout teaser is the default.
   */
  public function testLayoutTeaserNotDefault(): void {

    // Enable layout builder.
    LayoutBuilderEntityViewDisplay::load('node.test.teaser')
      ->enableLayoutBuilder()
      ->save();

    $query = <<<GQL
      query {
        a: nodeTest(id: "{$this->node->uuid()}") {
          sections {
            id
          }
        }

        b: nodeTest(id: "{$this->node->uuid()}") {
          sections(viewMode: "teaser") {
            id
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['a']);
    $this->assertNotEmpty($content['data']['b']);

    $this->assertNotEquals(
      $content['data']['a']['sections'],
      $content['data']['b']['sections']
    );

    // 2 sections on default
    $this->assertCount(2, $content['data']['a']['sections']);

    // Default 1 section on teaser.
    $this->assertCount(1, $content['data']['b']['sections']);
  }

  /**
   * Ensure an disabled view mode falls back to default.
   */
  public function testDisabledViewFallbackDefault(): void {

    $query = <<<GQL
      query {
        a: nodeTest(id: "{$this->node->uuid()}") {
          sections {
            id
          }
        }

        b: nodeTest(id: "{$this->node->uuid()}") {
          sections(viewMode: "teaser") {
            id
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['a']);
    $this->assertNotEmpty($content['data']['b']);

    $this->assertEquals(
      $content['data']['a']['sections'],
      $content['data']['b']['sections']
    );
  }

  /**
   * Ensure disabled entity type has no layout.
   */
  public function testDisabledEntityIsNull(): void {

    // Disable node.
    $this->setEntityConfig('node', 'another_type', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $node = $this->createNode([
      'type' => 'another_type',
    ]);

    $query = <<<GQL
      query {
        nodeAnotherType(id: "{$node->uuid()}") {
          sections {
            id
          }
        }
      }
    GQL;

    try {
      $content = $this->executeQuery($query);
    }
    catch (\Exception) {
      // Swallow error.
    }

    $this->assertStringContainsStringIgnoringCase(
      'Cannot query field "sections" on type "NodeAnotherType".',
      $content['errors'][0]['message']
    );
  }

  /**
   * Test the sections load and are in the correct order.
   */
  public function testLayoutSections(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            __typename
            id
            settings
            layout {
              category
              defaultRegion
              id
              label
              regions
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $this->assertCount(2, $node['sections']);

    // Three column.
    $this->assertEquals($node['sections'][0]['settings']['column_widths'], '25-25-50');
    $this->assertEquals($node['sections'][0]['layout']['id'], 'layout_threecol_section');
  }

  /**
   * Check a layout component is correctly loaded and has correct config.
   */
  public function testComponentConfig(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            components {
              id
              region
              weight
              configuration
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $first = array_filter($node['sections'][0]['components'], function ($component) {
      return $component['region'] === 'first';
    });
    $second = array_filter($node['sections'][0]['components'], function ($component) {
      return $component['region'] === 'second';
    });
    $third = array_filter($node['sections'][0]['components'], function ($component) {
      return $component['region'] === 'third';
    });

    $this->assertCount(1, $first);
    $this->assertCount(1, $second);
    $this->assertCount(1, $third);

    $first = reset($first);
    $second = reset($second);
    $third = reset($third);

    $this->assertIsInt($first['weight']);
    $this->assertIsInt($second['weight']);
    $this->assertIsInt($third['weight']);

    // System block.
    $this->assertEquals($first['configuration']['id'], 'system_powered_by_block');
    $this->assertEquals($first['configuration']['label'], 'Powered by Drupal');
    $this->assertEquals($first['configuration']['label_display'], 'visible');

    // Basic block content.
    $this->assertEquals($second['configuration']['id'], 'block_content:' . $this->blockContent->uuid());
    $this->assertEquals($second['configuration']['label'], 'My test block');
    $this->assertEquals($second['configuration']['label_display'], 'visible');

    // Inline basic block.
    $this->assertEquals($third['configuration']['id'], 'inline_block:basic_block');
    $this->assertEquals($third['configuration']['label'], 'My inline block');
    $this->assertIsInt($third['configuration']['label_display']);
    $this->assertEquals(0, $third['configuration']['label_display']);

    // Body field (after links field by default).
    $body = $node['sections'][1]['components'];

    $this->assertIsInt($body[0]['weight']);
    $this->assertIsInt($body[1]['weight']);

    // Just confirm its there.
    $this->assertEquals($body[1]['configuration']['id'], 'field_block:node:test:body');
  }

  /**
   * Check blocks can render.
   */
  public function testComponentBlockRender(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            components {
              block {
                ... on BlockInterface {
                  render
                }
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);
    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $first = $node['sections'][0]['components'][0];
    $second = $node['sections'][0]['components'][2];
    $third = $node['sections'][0]['components'][1];

    $body = $node['sections'][1]['components'][1];
    $title = $node['sections'][1]['components'][2];

    $this->assertStringContainsStringIgnoringCase(
      'Powered by',
      $first['block']['render']
    );

    $this->assertStringContainsStringIgnoringCase(
      'This is the block content',
      $second['block']['render']
    );

    $this->assertStringContainsStringIgnoringCase(
      'My inline block content',
      $third['block']['render']
    );

    $this->assertStringContainsStringIgnoringCase(
      'The node body',
      $body['block']['render']
    );

    $this->assertStringContainsStringIgnoringCase(
      'The node title',
      $title['block']['render']
    );
  }

  /**
   * Check layout builder field block has correct content.
   */
  public function testComponentBlockField(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            components {
              block {
                ... on BlockField {
                  id
                  title
                  fieldName
                  field {
                    ... on BlockFieldNodeTitle {
                      title
                    }
                    ... on BlockFieldNodeTestBody {
                      body {
                        processed
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);
    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $body = $node['sections'][1]['components'][1];
    $title = $node['sections'][1]['components'][2];

    $this->assertEquals('body', $body['block']['fieldName']);
    $this->assertNull($body['block']['title']);

    $this->assertStringContainsStringIgnoringCase(
      'The node body',
      $body['block']['field']['body']['processed']
    );

    $this->assertStringContainsStringIgnoringCase(
      'The node title',
      $title['block']['field']['title']
    );
  }

  /**
   * Check block content are typed correctly.
   */
  public function testComponentBlockContent(): void {
    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            components {
              block {
                ... on BlockContent {
                  id
                  title
                  entity {
                    ... on BlockContentBasicBlock {
                      id
                      title
                      body {
                        processed
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);
    $this->assertNotEmpty($content['data']['nodeTest']);
    $node = $content['data']['nodeTest'];

    $second = $node['sections'][0]['components'][2];
    $third = $node['sections'][0]['components'][1];

    // Uses the default block.
    $this->assertEquals($this->blockContent->uuid(), $second['block']['entity']['id']);
    $this->assertStringContainsStringIgnoringCase(
      'This is the block content',
      $second['block']['entity']['body']['processed']
    );

    // Check the overridden content is applied.
    $this->assertStringContainsStringIgnoringCase(
      'My inline block content',
      $third['block']['entity']['body']['processed']
    );
  }

  /**
   * Ensure a field isn't accessible if not exposed.
   */
  public function testHiddenFieldNotAccessible(): void {
    $this->setFieldConfig('node', 'test', 'body', [
      'enabled' => FALSE,
    ]);

    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          id
          sections {
            components {
              block {
                ... on BlockField {
                  id
                  title
                  render
                  field {
                    ... on BlockFieldNodeId {
                      id
                    }
                    ... on BlockFieldNodeTestBody {
                      body {
                        processed
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    GQL;

    try {
      $content = $this->executeQuery($query);
    }
    catch (\Exception) {
      // Swallow error.
    }

    $this->assertStringContainsStringIgnoringCase(
      'Unknown type "BlockFieldNodeTestBody"',
      $content['errors'][0]['message']
    );

    $body = $node['sections'][1]['components'][1] ?? NULL;

    $this->assertNull($body);
  }

}
