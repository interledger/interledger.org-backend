<?php

namespace Drupal\Tests\decoupled_router\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test class.
 *
 * @group decoupled_router
 */
class DecoupledRouterInfoAlterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Modules list.
   *
   * @var array
   */
  protected static $modules = [
    'decoupled_router',
    'node',
    'path',
    'jsonapi',
    'test_decoupled_router',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->user = $this->drupalCreateUser([
      'access content',
      'create article content',
    ]);
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests hook_decoupled_router_info_alter.
   *
   * Allow the "test_decoupled_router" module to add new values to the output.
   */
  public function testDecoupledRouterInfoAlterTest() {
    $values = [
      'uid' => ['target_id' => $this->user->id()],
      'type' => 'article',
      'path' => '/node--altered',
      'title' => $this->getRandomGenerator()->name(),
    ];
    $node = $this->createNode($values);

    // Test access to node.
    $res = $this->drupalGet(
      Url::fromRoute('decoupled_router.path_translation'), [
        'query' => [
          'path' => '/node--altered',
          '_format' => 'json',
        ],
      ]
    );
    $output = Json::decode($res);
    $this->assertSession()->statusCodeEquals(200);
    $expected = [
      'resolved' => $this->buildUrl('/node--altered'),
      'isHomePath' => FALSE,
      'entity' => [
        'canonical' => $this->buildUrl('/node--altered'),
        'type' => 'node',
        'bundle' => 'article',
        'id' => $node->id(),
        'uuid' => $node->uuid(),
        // Result of implementing the hook_decoupled_router_info_alter.
        'owner' => $node->getOwner()->uuid(),
        'langcode' => 'en',
      ],
      'label' => $node->label(),
      'jsonapi' => [
        'individual' => $this->buildUrl('/jsonapi/node/article/' . $node->uuid()),
        'resourceName' => 'node--article',
        'pathPrefix' => 'subdirectory/jsonapi',
        'basePath' => '/subdirectory/jsonapi',
        'entryPoint' => $this->buildUrl('/jsonapi'),
      ],
      'meta' => [
        'deprecated' => [
          'jsonapi.pathPrefix' => 'This property has been deprecated and will be removed in the next version of Decoupled Router. Use basePath instead.',
        ],
      ],
    ];
    $this->assertEquals($expected, $output);

  }

}
