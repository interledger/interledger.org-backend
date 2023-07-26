<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use GraphQL\Error\DebugFlag;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Tests your GraphQL functionality.
 *
 * @group graphql_compose
 */
abstract class GraphQLComposeBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'graphql_compose',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The GraphQL endpoint.
   *
   * @var string
   */
  protected string $graphqlEndpointUrl = '/graphql';

  /**
   * The GraphQL permissions required to view the schema.
   *
   * @var array
   */
  protected array $graphqlPermissions = [
    'execute graphql_compose_server arbitrary graphql requests',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->grantPermissions(
      Role::load(Role::ANONYMOUS_ID),
      $this->graphqlPermissions
    );

    // GraphQL Config.
    $settings['settings']['graphql.graphql_servers.core_graphql']['debug_flag'] = (object) [
      'value' => DebugFlag::INCLUDE_DEBUG_MESSAGE,
      'required' => TRUE,
    ];

    $settings['settings']['graphql.graphql_servers.core_graphql']['caching'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * Executes a query.
   *
   * @param string $query
   *   The query to execute.
   * @param array $variables
   *   The query variables.
   *
   * @return array
   *   The query json result.
   */
  protected function executeQuery(string $query, array $variables = []): array {

    $url = $this->buildUrl($this->graphqlEndpointUrl, ['absolute' => TRUE]);

    try {
      $response = $this->getHttpClient()->request('POST', $url, [
        'json' => [
          'query' => $query,
          'variables' => $variables,
        ],
        'cookies' => $this->getSessionCookies(),
      ]);
    }
    catch (RequestException | ClientException $e) {
      print_r($query);
      print_r($e->getMessage());
      var_dump($e->getResponse()->getBody()->getContents());
      throw $e;
    }
    $this->assertEquals(200, $response->getStatusCode());

    return Json::decode($response->getBody());
  }

  /**
   * Set an entity config.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_bundle_id
   *   The entity bundle id.
   * @param array $options
   *   The options to set.
   */
  protected function setEntityConfig(string $entity_type_id, string $entity_bundle_id, array $options = []): void {
    $settings['config']['graphql_compose.settings']['entity_config'][$entity_type_id][$entity_bundle_id] = $this->mapDeepest(function ($value) {
      return (object) ['value' => $value, 'required' => TRUE];
    }, $options);

    $this->writeSettings($settings);
  }

  /**
   * Set an entity config.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_bundle_id
   *   The entity bundle id.
   * @param string $field_name
   *   The field name.
   * @param array $options
   *   The options to set.
   */
  protected function setFieldConfig(string $entity_type_id, string $entity_bundle_id, string $field_name, array $options = []): void {
    $settings['config']['graphql_compose.settings']['field_config'][$entity_type_id][$entity_bundle_id][$field_name] = $this->mapDeepest(function ($value) {
      return (object) ['value' => $value, 'required' => TRUE];
    }, $options);

    $this->writeSettings($settings);
  }

  /**
   * Map an array to the deepest level.
   *
   * @param \Closure $callback
   *   The callback to apply.
   * @param array $array
   *   The array to map.
   *
   * @return array
   *   The mapped array.
   */
  protected function mapDeepest(\Closure $callback, array $array): array {
    $func = function ($item) use (&$func, $callback) {
      return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
    };

    return array_map($func, $array);
  }

}
