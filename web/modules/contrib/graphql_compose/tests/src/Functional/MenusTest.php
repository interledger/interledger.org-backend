<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\system\MenuInterface;

/**
 * Tests specific to GraphQL Compose menus.
 *
 * @group graphql_compose
 */
class MenusTest extends GraphQLComposeBrowserTestBase {

  /**
   * The test menu.
   *
   * @var \Drupal\system\Entity\MenuInterface
   */
  protected MenuInterface $menu;

  /**
   * The test links.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected array $links;

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_link_content',
    'graphql_compose_menus',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->menu = Menu::create([
      'id' => 'test',
      'label' => 'Test Menu',
    ]);

    $this->menu->save();

    $this->nodes[1] = $this->createNode([
      'title' => 'Test node 1',
    ]);

    $this->nodes[2] = $this->createNode([
      'title' => 'Test node 2',
    ]);

    $this->links[1] = MenuLinkContent::create([
      'title' => 'Test link 1',
      'link' => ['uri' => 'internal:/node/' . $this->nodes[1]->id()],
      'menu_name' => $this->menu->id(),
      'weight' => 10,
    ]);

    $this->links[2] = MenuLinkContent::create([
      'title' => 'Test link 2',
      'link' => ['uri' => 'internal:/node/' . $this->nodes[2]->id()],
      'menu_name' => $this->menu->id(),
      'weight' => 5,
    ]);

    $this->links[3] = MenuLinkContent::create([
      'title' => 'Test link child',
      'link' => ['uri' => 'internal:/node/' . $this->nodes[1]->id()],
      'menu_name' => $this->menu->id(),
      'parent' => $this->links[1]->getPluginId(),
    ]);

    $this->links[4] = MenuLinkContent::create([
      'title' => 'Test disabled',
      'link' => ['uri' => 'internal:/'],
      'menu_name' => $this->menu->id(),
      'weight' => 5,
      'enabled' => FALSE,
    ]);

    foreach ($this->links as $link) {
      $link->save();
    }

    $this->setEntityConfig('menu', 'test', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Test menu links by name.
   */
  public function testMenuLoadByName(): void {
    $query = <<<GQL
      query {
        menu(name: TEST) {
          id
          name
          items {
            title
            url
            internal
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $menu = $content['data']['menu'];

    $this->assertEquals($this->menu->uuid(), $menu['id']);
    $this->assertEquals($this->menu->label(), $menu['name']);

    $this->assertCount(2, $menu['items']);

    // Sort weight sorting.
    $this->assertEquals('Test link 2', $menu['items'][0]['title']);

    // Test internal link.
    $this->assertTrue($menu['items'][0]['internal']);
  }

  /**
   * Test menu link parents.
   */
  public function testMenuParents(): void {

    $query = <<<GQL
      query {
        menu(name: TEST) {
          items {
            title
            children {
              title
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);

    $menu = $content['data']['menu'];

    $this->assertEmpty($menu['items'][0]['children']);

    $this->assertEquals('Test link child', $menu['items'][1]['children'][0]['title']);
  }

}
