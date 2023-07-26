<?php

declare(strict_types=1);

namespace Drupal\Tests\graphql_compose\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\NodeInterface;

/**
 * Tests specific to GraphQL Compose entity type: Node.
 *
 * @group graphql_compose
 */
class EntityCommentTest extends GraphQLComposeBrowserTestBase {

  use CommentTestTrait;

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'graphql_compose_comments',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType([
      'type' => 'test',
      'name' => 'Test node type',
    ]);

    $this->addDefaultCommentField('node', 'test', 'comments');

    $this->node = $this->createNode([
      'type' => 'test',
      'title' => 'Test',
      'body' => [
        'value' => 'Test content',
        'format' => 'plain_text',
      ],
      'status' => 1,
    ]);

    $this->setEntityConfig('node', 'test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $this->setFieldConfig('node', 'test', 'comments', [
      'enabled' => TRUE,
    ]);

    $this->setEntityConfig('comment', 'comment', [
      'enabled' => TRUE,
      'comments_mutation_enabled' => TRUE,
      'edges_enabled' => TRUE,
    ]);

    $this->setFieldConfig('comment', 'comment', 'comment_body', [
      'enabled' => TRUE,
    ]);

  }

  /**
   * Create a comment for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to create a comment for.
   *
   * @return \Drupal\comment\CommentInterface
   *   The created comment.
   */
  protected function createComment(NodeInterface $node): CommentInterface {
    $payload = [
      'comment_type' => 'comment',
      'entity_type'  => 'node',
      'entity_id'    => $node->id(),
      'field_name'   => 'comments',
      'subject'      => 'Test subject',
      'comment_body' => [
        'value' => 'Test comment for ' . $node->id() . ' : ' . uniqid(),
        'format' => 'plain_text',
      ],
      'status' => TRUE,
    ];

    $comment = Comment::create($payload);
    $comment->save();

    return $comment;
  }

  /**
   * Check comments on a node.
   */
  public function testNodeCommentFieldRead() {

    $comment = $this->createComment($this->node);

    $query = <<<GQL
      query {
        nodeTest(id: "{$this->node->uuid()}") {
          comments(first: 10) {
            nodes {
              id
              subject
              commentBody {
                value
              }
            }
          }
        }
      }
    GQL;

    // Try as anonymous.
    $content = $this->executeQuery($query);

    $this->assertNull($content['data']['nodeTest']['comments']);

    // Now try as user with permission.
    $privilegedUser = $this->createUser([
      'access comments',
      'access content',
      ...$this->graphqlPermissions,
    ]);
    $this->drupalLogin($privilegedUser);

    $content = $this->executeQuery($query);

    $this->assertEquals(
      $comment->uuid(),
      $content['data']['nodeTest']['comments']['nodes'][0]['id']
    );

  }

  /**
   * Check comments writable to a node.
   */
  public function testNodeCommentFieldWrite() {

    $mutation = <<<GQL
      mutation(\$data: CommentCommentInput!) {
        addCommentComment(data: \$data) {
          id
          subject
          commentBody {
            value
          }
        }
      }
    GQL;

    $data = [
      'entityType' => 'NodeTest',
      'entityId' => $this->node->uuid(),
      'commentBody' => 'This is a test',
    ];

    // Try as anonymous.
    $content = $this->executeQuery($mutation, ['data' => $data]);
    $this->assertSame(
      'You do not have permission to post comments.',
      $content['errors'][0]['message']
    );

    // Setup comment privileged user.
    $privilegedUser = $this->createUser([
      'post comments',
      'access comments',
      'skip comment approval',
      ...$this->graphqlPermissions,
    ]);

    $this->drupalLogin($privilegedUser);

    $content = $this->executeQuery($mutation, ['data' => $data]);

    $this->assertEquals(
      $data['commentBody'],
      $content['data']['addCommentComment']['commentBody']['value']
    );
  }

}
