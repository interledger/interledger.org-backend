<?php

namespace Drupal\Tests\decoupled_router\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\NodeInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\BrowserTestBase;

/**
 * Test class.
 *
 * @group decoupled_router
 */
class DecoupledRouterFunctionalTest extends BrowserTestBase {

  const DRUPAL_CI_BASE_URL = 'http://localhost/subdir';

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
   * The nodes.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * Modules list.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'node',
    'path',
    'decoupled_router',
    'redirect',
    'jsonapi',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $language = ConfigurableLanguage::createFromLangcode('ca');
    $language->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    \Drupal::configFactory()->getEditable('language.negotiation')
      ->set('url.prefixes.ca', 'ca')
      ->save();
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->user = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->createDefaultContent(3);
    $redirect = Redirect::create(['status_code' => '301']);
    $redirect->setSource('/foo');
    $redirect->setRedirect('/node--0');
    $redirect->setLanguage(Language::LANGCODE_NOT_SPECIFIED);
    $redirect->save();
    $redirect = Redirect::create(['status_code' => '301']);
    $redirect->setSource('/bar');
    $redirect->setRedirect('/foo');
    $redirect->setLanguage(Language::LANGCODE_NOT_SPECIFIED);
    $redirect->save();
    $redirect = Redirect::create(['status_code' => '301']);
    $redirect->setSource('/foo--ca');
    $redirect->setRedirect('/node--0--ca');
    $redirect->setLanguage('ca');
    $redirect->save();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Creates default content to test the API.
   *
   * @param int $num_articles
   *   Number of articles to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createDefaultContent($num_articles) {
    $random = $this->getRandomGenerator();
    for ($created_nodes = 0; $created_nodes < $num_articles; $created_nodes++) {
      $values = [
        'uid' => ['target_id' => $this->user->id()],
        'type' => 'article',
        'path' => '/node--' . $created_nodes,
        'title' => $random->name(),
      ];
      $node = $this->createNode($values);
      $values['title'] = $node->getTitle() . ' (ca)';
      $values['field_image']['alt'] = 'alt text (ca)';
      $values['path'] = '/node--' . $created_nodes . '--ca';
      $node->addTranslation('ca', $values);

      $node->save();
      $this->nodes[] = $node;
    }
  }

  /**
   * Tests reading multilingual content.
   */
  public function testNegotiationNoMultilingual() {
    // This is not build with data providers to avoid rebuilding the environment
    // each test.
    $make_assertions = function ($path, DecoupledRouterFunctionalTest $test) {
      $res = $test->drupalGet(
        Url::fromRoute('decoupled_router.path_translation'),
        [
          'query' => [
            'path' => $path,
            '_format' => 'json',
          ],
        ]
      );
      $test->assertSession()->statusCodeEquals(200);
      $output = Json::decode($res);
      $test->assertStringEndsWith('/node--0', $output['resolved']);
      $test->assertSame($test->nodes[0]->id(), $output['entity']['id']);
      $test->assertSame('node--article', $output['jsonapi']['resourceName']);
      $test->assertStringEndsWith('/jsonapi/node/article/' . $test->nodes[0]->uuid(), $output['jsonapi']['individual']);
    };

    $base_path = $this->getBasePath();

    // Test cases:
    $test_cases = [
      // 1. Test negotiation by system path for /node/1 -> /node--0.
      $base_path . '/node/1',
      // 2. Test negotiation by alias for /node--0.
      $base_path . '/node--0',
      // 3. Test negotiation by multiple redirects for /bar -> /foo -> /node--0.
      $base_path . '/bar',
    ];
    array_walk($test_cases, function ($test_case) use ($make_assertions) {
      $make_assertions($test_case, $this);
    });
  }

  /**
   * Test that unpublished content ist not available.
   */
  public function testUnpublishedContent() {
    $values = [
      'uid' => ['target_id' => $this->user->id()],
      'type' => 'article',
      'path' => '/node--unpublished',
      'title' => $this->getRandomGenerator()->name(),
      'status' => NodeInterface::NOT_PUBLISHED,
    ];
    $node = $this->createNode($values);

    $redirect = Redirect::create(['status_code' => '301']);
    $redirect->setSource('/unp');
    $redirect->setRedirect('/node--unpublished');
    $redirect->setLanguage(Language::LANGCODE_NOT_SPECIFIED);
    $redirect->save();

    // Test access via node_id to unpublished content.
    $res = $this->drupalGet(
      Url::fromRoute('decoupled_router.path_translation'),
      [
        'query' => [
          'path' => '/unp',
          '_format' => 'json',
        ],
      ]
    );
    $output = Json::decode($res);
    $this->assertArrayNotHasKey('redirect', $output);
    $this->assertEquals(
      [
        'message' => 'Access denied for entity.',
        'details' => 'This user does not have access to view the resolved entity. Please authenticate and try again.',
      ],
      $output
    );
    $this->assertSession()->statusCodeEquals(403);

    // Make sure priviledged users can access the output.
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
    // Test access via node_id to unpublished content.
    $res = $this->drupalGet(
      Url::fromRoute('decoupled_router.path_translation'),
      [
        'query' => [
          'path' => '/unp',
          '_format' => 'json',
        ],
      ]
    );
    $output = Json::decode($res);
    $this->assertSession()->statusCodeEquals(200);
    $expected = [
      'resolved' => $this->buildUrl('/node--unpublished'),
      'isHomePath' => FALSE,
      'entity' => [
        'canonical' => $this->buildUrl('/node--unpublished'),
        'type' => 'node',
        'bundle' => 'article',
        'id' => $node->id(),
        'uuid' => $node->uuid(),
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
      'redirect' => [
        [
          'from' => '/unp',
          'to' => '/' . implode('/', array_filter([
            trim($this->getBasePath(), '/'),
            'node--unpublished',
          ])),
          'status' => '301',
        ],
      ],
    ];
    $this->assertEquals($expected, $output);
  }

  /**
   * Test that the home path check is working.
   */
  public function testHomPathCheck() {

    // Create front page node.
    $this->createNode([
      'uid' => ['target_id' => $this->user->id()],
      'type' => 'article',
      'path' => '/node--homepage',
      'title' => $this->getRandomGenerator()->name(),
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);

    // Update front page.
    \Drupal::configFactory()->getEditable('system.site')
      ->set('page.front', '/node--homepage')
      ->save();

    $user = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($user);

    // Test front page node.
    $res = $this->drupalGet(
      Url::fromRoute('decoupled_router.path_translation'),
      [
        'query' => [
          'path' => '/node--homepage',
          '_format' => 'json',
        ],
      ]
    );
    $this->assertSession()->statusCodeEquals(200);
    $output = Json::decode($res);
    $this->assertTrue($output['isHomePath']);

    // Test non-front page node.
    $res = $this->drupalGet(
      Url::fromRoute('decoupled_router.path_translation'),
      [
        'query' => [
          'path' => '/node--1',
          '_format' => 'json',
        ],
      ]
    );
    $this->assertSession()->statusCodeEquals(200);
    $output = Json::decode($res);
    $this->assertFalse($output['isHomePath']);
  }

  /**
   * Tests path argument with prefix other than negotiated language.
   */
  public function testUrlLanguageNegotiation() {
    $german = ConfigurableLanguage::createFromLangcode('de');
    $german->save();
    $german_node = $this->createNode([
      'uid' => ['target_id' => $this->user->id()],
      'type' => 'article',
      'path' => '/hallo-welt',
      'title' => 'Hallo Welt',
      'langcode' => 'de',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalGet($german_node->toUrl()->toString());

    $this->assertSession()->pageTextContains('Hallo Welt');

    $this->drupalGet(Url::fromRoute('decoupled_router.path_translation'), [
        'query' => [
          'path' => '/de/hallo-welt',
          '_format' => 'json',
        ],
      ]);
    $output = $this->getSession()->getPage()->getContent();
    // Running tests in chromedriver returns html wrapped around the JSON.
    if (strpos($output, '<pre') !== FALSE) {
      $output = $this->assertSession()->elementExists('css', 'pre')->getHtml();
    }
    $output = Json::decode($output);
    $this->assertStringEndsWith('/de/hallo-welt', $output['resolved']);
    $this->assertSame($german_node->id(), $output['entity']['id']);
    $this->assertSame('node--article', $output['jsonapi']['resourceName']);
    $this->assertStringEndsWith('/jsonapi/node/article/' . $german_node->uuid(), $output['jsonapi']['individual']);
  }

  /**
   * Computes the base path under which the Drupal managed URLs are available.
   *
   * @return string
   *   The path.
   */
  private function getBasePath() {
    $parts = parse_url(
      (
        getenv('SIMPLETEST_BASE_URL') ?: getenv('WEB_HOST')
      ) ?: self::DRUPAL_CI_BASE_URL
    );
    return empty($parts['path']) ? '/' : $parts['path'];
  }

}
