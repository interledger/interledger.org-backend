<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests specific to GraphQL Compose entity type: User.
 *
 * @group graphql_compose
 */
class EntityUserTest extends GraphQLComposeBrowserTestBase {

  use UserCreationTrait;

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'graphql_compose_users',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([], NULL, FALSE, [
      'mail' => 'test@test.com',
    ]);

    $this->setEntityConfig('user', 'user', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);
  }

  /**
   * Test admin user loading.
   */
  public function testUserLoadAsAdmin(): void {

    $currentUser = $this->createUser([], NULL, TRUE);

    $this->drupalLogin($currentUser);

    $query = <<<GQL
      query {
        user(id: "{$this->user->uuid()}") {
          id
          mail
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->user->uuid(), $content['data']['user']['id']);
    $this->assertEquals($this->user->getEmail(), $content['data']['user']['mail']);
  }

  /**
   * Check as a user with profile and mail access.
   */
  public function testUserLoadAsElevated(): void {

    $currentUser = $this->createUser([
      'access user profiles',
      'view user email addresses',
      ... $this->graphqlPermissions,
    ]);

    $this->drupalLogin($currentUser);

    $query = <<<GQL
      query {
        user(id: "{$this->user->uuid()}") {
          id
          mail
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->user->uuid(), $content['data']['user']['id']);
    $this->assertEquals($this->user->getEmail(), $content['data']['user']['mail']);
  }

  /**
   * Check as a user with profile access only.
   */
  public function testUserLoadAsUser(): void {

    $currentUser = $this->createUser([
      'access user profiles',
      ... $this->graphqlPermissions,
    ]);

    $this->drupalLogin($currentUser);

    $query = <<<GQL
      query {
        user(id: "{$this->user->uuid()}") {
          id
          mail
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertEquals($this->user->uuid(), $content['data']['user']['id']);
    $this->assertNull($content['data']['user']['mail']);
  }

  /**
   * Check as a user with no permissions.
   */
  public function testUserLoadAsRestricted(): void {

    $query = <<<GQL
      query {
        user(id: "{$this->user->uuid()}") {
          id
          mail
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $this->assertNull($content['data']['user']);
  }

}
