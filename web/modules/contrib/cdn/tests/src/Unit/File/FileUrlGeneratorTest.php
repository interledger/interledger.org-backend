<?php

declare(strict_types = 1);

namespace Drupal\Tests\cdn\Unit\File;

use Drupal\Core\File\FileUrlGenerator as CoreFileUrlGenerator;
use Drupal\cdn\CdnSettings;
use Drupal\cdn\File\FileUrlGenerator;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\cdn\File\FileUrlGenerator
 * @group cdn
 */
class FileUrlGeneratorTest extends UnitTestCase {

  /**
   * A hardcoded return value for the mocked core file URL generator service.
   *
   * @var string
   */
  const DECORATED_SERVICE_RETURNED_URL = '//decorated';

  /**
   * The private key to use in tests.
   *
   * @var string
   */
  protected static $privateKey = 'super secret key that really is just some string';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $settings = [
      'hash_salt' => $this->randomMachineName(),
    ];
    new Settings($settings);
  }

  /**
   * @covers ::generateString
   * @covers ::generate
   * @covers ::generateAbsoluteString
   * @covers ::doGenerate
   * @dataProvider urlProvider
   */
  public function testGenerate($scheme, $base_path, $uri, $expected_result) {
    $gen = $this->createFileUrlGenerator($base_path, [
      'status' => TRUE,
      'mapping' => [
        'type' => 'complex',
        'fallback_domain' => 'cdn.example.com',
        'domains' => [
          0 => [
            'type' => 'simple',
            'domain' => 'static.example.com',
            'conditions' => [
              'extensions' => ['css', 'js'],
            ],
          ],
          1 => [
            'type' => 'auto-balanced',
            'domains' => [
              'img1.example.com',
              'img2.example.com',
            ],
            'conditions' => [
              'extensions' => ['jpg', 'jpeg', 'png'],
            ],
          ],
        ],
      ],
      'scheme' => $scheme,
      'farfuture' => [
        'status' => FALSE,
      ],
      'stream_wrappers' => ['public'],
    ]);
    $this->assertSame($expected_result, $gen->generateString($uri));
    $this->assertSame($expected_result, $gen->generateAbsoluteString($uri));
    $this->assertSame($expected_result, $gen->generate($uri)->toUriString());
  }

  public function urlProvider() {
    $cases_root = [
      'absolute URL' => [
        'http://example.com/llama.jpg',
        FALSE,
      ],
      'scheme-relative URL' => [
        '//example.com/llama.jpg',
        FALSE,
      ],
      'shipped file (fallback)' => [
        'core/misc/something.else',
        '//cdn.example.com/core/misc/something.else',
      ],
      'shipped file (simple)' => [
        'core/misc/simple.css',
        '//static.example.com/core/misc/simple.css',
      ],
      'shipped file (auto-balanced)' => [
        'core/misc/auto-balanced.png',
        '//img2.example.com/core/misc/auto-balanced.png',
      ],
      'shipped file with querystring (e.g. in url() in CSS)' => [
        'core/misc/something.else?foo=bar&baz=qux',
        '//cdn.example.com/core/misc/something.else?foo=bar&baz=qux',
      ],
      'shipped file with fragment (e.g. in url() in CSS)' => [
        'core/misc/something.else#llama',
        '//cdn.example.com/core/misc/something.else#llama',
      ],
      'shipped file with querystring & fragment (e.g. in url() in CSS)' => [
        'core/misc/something.else?foo=bar&baz=qux#llama',
        '//cdn.example.com/core/misc/something.else?foo=bar&baz=qux#llama',
      ],
      'managed public file (fallback)' => [
        'public://something.else',
        '//cdn.example.com/sites/default/files/something.else',
      ],
      'managed public file (spublic public imple)' => [
        'public://simple.css',
        '//static.example.com/sites/default/files/simple.css',
      ],
      'managed public file (auto-balanced)' => [
        'public://auto-balanced.png',
        '//img2.example.com/sites/default/files/auto-balanced.png',
      ],
      'managed private file (fallback)' => [
        'private://something.else',
        FALSE,
      ],
      'unicode' => [
        'public://újjáépítésérol — 100% in B&W.jpg',
        '//img1.example.com/sites/default/files/%C3%BAjj%C3%A1%C3%A9p%C3%ADt%C3%A9s%C3%A9rol%20%E2%80%94%20100%25%20in%20B%26W.jpg',
      ],
      'encoded' => [
        'public://llama%20.jpg',
        '//img2.example.com/sites/default/files/llama%2520.jpg',
      ],
      'reserved characters in RFC3986' => [
        'public://gendelims :?#[]@ subdelims !$&\'()*+,;=.something',
        '//cdn.example.com/sites/default/files/gendelims%20%3A%3F%23%5B%5D%40%20subdelims%20%21%24%26%27%28%29%2A%2B%2C%3B%3D.something',
      ],
    ];

    $cases_subdir = [
      'absolute URL' => [
        'http://example.com/llama.jpg',
        FALSE,
      ],
      'scheme-relative URL' => [
        '//example.com/llama.jpg',
        FALSE,
      ],
      'shipped file (fallback)' => [
        'core/misc/feed.svg',
        '//cdn.example.com/subdir/core/misc/feed.svg',
      ],
      'shipped file (simple)' => [
        'core/misc/simple.css',
        '//static.example.com/subdir/core/misc/simple.css',
      ],
      'shipped file (auto-balanced)' => [
        'core/misc/auto-balanced.png',
        '//img2.example.com/subdir/core/misc/auto-balanced.png',
      ],
      'shipped file with querystring (e.g. in url() in CSS)' => [
        'core/misc/something.else?foo=bar&baz=qux',
        '//cdn.example.com/subdir/core/misc/something.else?foo=bar&baz=qux',
      ],
      'shipped file with fragment (e.g. in url() in CSS)' => [
        'core/misc/something.else#llama',
        '//cdn.example.com/subdir/core/misc/something.else#llama',
      ],
      'shipped file with querystring & fragment (e.g. in url() in CSS)' => [
        'core/misc/something.else?foo=bar&baz=qux#llama',
        '//cdn.example.com/subdir/core/misc/something.else?foo=bar&baz=qux#llama',
      ],
      'managed public file (fallback)' => [
        'public://something.else',
        '//cdn.example.com/subdir/sites/default/files/something.else',
      ],
      'managed public file (simple)' => [
        'public://simple.css',
        '//static.example.com/subdir/sites/default/files/simple.css',
      ],
      'managed public file (auto-balanced)' => [
        'public://auto-balanced.png',
        '//img2.example.com/subdir/sites/default/files/auto-balanced.png',
      ],
      'managed private file (fallback)' => [
        'private://something.else',
        FALSE,
      ],
      'unicode' => [
        'public://újjáépítésérol — 100% in B&W.jpg',
        '//img1.example.com/subdir/sites/default/files/%C3%BAjj%C3%A1%C3%A9p%C3%ADt%C3%A9s%C3%A9rol%20%E2%80%94%20100%25%20in%20B%26W.jpg',
      ],
      'encoded' => [
        'public://llama%20.jpg',
        '//img2.example.com/subdir/sites/default/files/llama%2520.jpg',
      ],
      'reserved characters in RFC3986' => [
        'public://gendelims :?#[]@ subdelims !$&\'()*+,;=.something',
        '//cdn.example.com/subdir/sites/default/files/gendelims%20%3A%3F%23%5B%5D%40%20subdelims%20%21%24%26%27%28%29%2A%2B%2C%3B%3D.something',
      ],
    ];

    return $this->getCasesWithScheme($cases_root, $cases_subdir);
  }

  /**
   * @covers ::generate
   * @dataProvider urlProviderForNegatedCondition
   */
  public function testGenerateWithNegatedCondition($scheme, $base_path, $uri, $expected_result) {
    $gen = $this->createFileUrlGenerator($base_path, [
      'status' => TRUE,
      'mapping' => [
        'type' => 'simple',
        'domain' => 'cdn.example.com',
        'conditions' => [
          'not' => [
            'extensions' => ['css', 'js'],
          ],
        ],
      ],
      'scheme' => $scheme,
      'farfuture' => [
        'status' => FALSE,
      ],
      'stream_wrappers' => ['public'],
    ]);
    $this->assertSame($expected_result, $gen->generateString($uri));
    $this->assertSame($expected_result, $gen->generateAbsoluteString($uri));
    $this->assertSame($expected_result, $gen->generate($uri)->toUriString());
  }

  public function urlProviderForNegatedCondition() {
    $cases_root = [
      'absolute URL' => [
        'http://example.com/llama.jpg',
        FALSE,
      ],
      'scheme-relative URL' => [
        '//example.com/llama.jpg',
        FALSE,
      ],
      'shipped file (simple, excluded)' => [
        'core/misc/simple.css',
        FALSE,
      ],
      'shipped file (simple)' => [
        'core/misc/simple.jpg',
        '//cdn.example.com/core/misc/simple.jpg',
      ],
      'shipped file with querystring (e.g. in url() in CSS)' => [
        'core/misc/something-else.css?foo=bar&baz=qux',
        FALSE,
      ],
      'shipped file with fragment (e.g. in url() in CSS)' => [
        'core/misc/something-else.css#llama',
        FALSE,
      ],
      'shipped file with querystring & fragment (e.g. in url() in CSS)' => [
        'core/misc/something-else.css?foo=bar&baz=qux#llama',
        FALSE,
      ],
      'managed public file' => [
        'public://something.else',
        '//cdn.example.com/sites/default/files/something.else',
      ],
      'managed public file (public simple css)' => [
        'public://simple.css',
        FALSE,
      ],
      'managed public file (public simple js)' => [
        'public://simple.js',
        FALSE,
      ],
      'managed public file (public simple anything-else)' => [
        'public://simple.jpg',
        '//cdn.example.com/sites/default/files/simple.jpg',
      ],
      'managed private file' => [
        'private://something.else',
        FALSE,
      ],
      'unicode' => [
        'public://újjáépítésérol — 100% in B&W.jpg',
        '//cdn.example.com/sites/default/files/%C3%BAjj%C3%A1%C3%A9p%C3%ADt%C3%A9s%C3%A9rol%20%E2%80%94%20100%25%20in%20B%26W.jpg',
      ],
      'encoded' => [
        'public://llama%20.jpg',
        '//cdn.example.com/sites/default/files/llama%2520.jpg',
      ],
      'reserved characters in RFC3986' => [
        'public://gendelims :?#[]@ subdelims !$&\'()*+,;=.something',
        '//cdn.example.com/sites/default/files/gendelims%20%3A%3F%23%5B%5D%40%20subdelims%20%21%24%26%27%28%29%2A%2B%2C%3B%3D.something',
      ],
    ];

    $cases_subdir = [
      'absolute URL' => [
        'http://example.com/llama.jpg',
        FALSE,
      ],
      'scheme-relative URL' => [
        '//example.com/llama.jpg',
        FALSE,
      ],
      'shipped file (simple, excluded)' => [
        'core/misc/simple.css',
        FALSE,
      ],
      'shipped file (simple)' => [
        'core/misc/simple.jpg',
        '//cdn.example.com/subdir/core/misc/simple.jpg',
      ],
      'shipped file with querystring (e.g. in url() in CSS)' => [
        'core/misc/something-else.css?foo=bar&baz=qux',
        FALSE,
      ],
      'shipped file with fragment (e.g. in url() in CSS)' => [
        'core/misc/something-else.css#llama',
        FALSE,
      ],
      'shipped file with querystring & fragment (e.g. in url() in CSS)' => [
        'core/misc/something-else.css?foo=bar&baz=qux#llama',
        FALSE,
      ],
      'managed public file' => [
        'public://something.else',
        '//cdn.example.com/subdir/sites/default/files/something.else',
      ],
      'managed public file (public simple css)' => [
        'public://simple.css',
        FALSE,
      ],
      'managed public file (public simple js)' => [
        'public://simple.js',
        FALSE,
      ],
      'managed public file (public simple anything-else)' => [
        'public://simple.jpg',
        '//cdn.example.com/subdir/sites/default/files/simple.jpg',
      ],
      'managed private file' => [
        'private://something.else',
        FALSE,
      ],
      'unicode' => [
        'public://újjáépítésérol — 100% in B&W.jpg',
        '//cdn.example.com/subdir/sites/default/files/%C3%BAjj%C3%A1%C3%A9p%C3%ADt%C3%A9s%C3%A9rol%20%E2%80%94%20100%25%20in%20B%26W.jpg',
      ],
      'encoded' => [
        'public://llama%20.jpg',
        '//cdn.example.com/subdir/sites/default/files/llama%2520.jpg',
      ],
      'reserved characters in RFC3986' => [
        'public://gendelims :?#[]@ subdelims !$&\'()*+,;=.something',
        '//cdn.example.com/subdir/sites/default/files/gendelims%20%3A%3F%23%5B%5D%40%20subdelims%20%21%24%26%27%28%29%2A%2B%2C%3B%3D.something',
      ],
    ];

    return $this->getCasesWithScheme($cases_root, $cases_subdir);
  }

  /**
   * Builds dataProvider for given cases on root and subdir scenarios.
   *
   * @param array $cases_root
   *   An array of scenarios at root level.
   * @param array $cases_subdir
   *   An array of scenarios at subdir level.
   *
   * @return array
   *   A dataProvider array of urls to test.
   */
  private function getCasesWithScheme(array $cases_root, array $cases_subdir): array {
    $cases = [];
    assert(count($cases_root) === count($cases_subdir));
    foreach ($cases_root as $description => $case) {
      $cases['root, ' . $description] = array_merge([''], $case);
    }
    foreach ($cases_subdir as $description => $case) {
      $cases['subdir, ' . $description] = array_merge(['/subdir'], $case);
    }

    // Generate `https://`, `http://` and `//` permutations for each case.
    $cases_with_scheme = [];
    foreach ($cases as $description => $case) {
      foreach (['https://', 'http://', '//'] as $scheme) {
        [$base_path, $uri, $expected_result] = $case;
        $cases_with_scheme['scheme=' . $scheme . ', ' . $description] = [
          $scheme,
          $base_path,
          $uri,
          !is_string($expected_result) ? static::DECORATED_SERVICE_RETURNED_URL : $scheme . substr($expected_result, 2),
        ];
      }
    }

    return $cases_with_scheme;
  }

  /**
   * @covers ::generateString
   * @covers ::generate
   * @covers ::generateAbsoluteString
   * @covers ::doGenerate
   */
  public function testGenerateFarfuture() {
    $config = [
      'status' => TRUE,
      'mapping' => [
        'type' => 'simple',
        'domain' => 'cdn.example.com',
        'conditions' => [],
      ],
      'scheme' => '//',
      'farfuture' => [
        'status' => TRUE,
      ],
      // File is used here generically to test a stream wrapper that is not
      // shipped with Drupal, but is natively supported by PHP.
      // @see \Drupal\cdn\File\FileUrlGenerator::generate(), which uses
      // file_exists() and would require actually configuring the stream
      // wrapper in the context of the unit test.
      'stream_wrappers' => ['public', 'file'],
    ];

    // Generate file for testing managed file.
    $llama_jpg_filename = 'llama%20 (' . $this->randomMachineName() . ').jpg';
    $llama_jpg_filepath = $this->root . '/sites/default/files/' . $llama_jpg_filename;
    file_put_contents($llama_jpg_filepath, $this->randomMachineName());
    $llama_jpg_mtime = filemtime($llama_jpg_filepath);
    $this->assertTrue(file_exists($llama_jpg_filepath));

    // In root: 1) non-existing file, 2) shipped file, 3) managed file.
    $gen = $this->createFileUrlGenerator('', $config);
    $this->assertSame('//cdn.example.com/core/misc/does-not-exist.js', $gen->generateString('core/misc/does-not-exist.js'));
    $drupal_js_mtime = filemtime($this->root . '/core/misc/drupal.js');
    $drupal_js_security_token = Crypt::hmacBase64($drupal_js_mtime . ':relative:' . UrlHelper::encodePath('/core/misc/drupal.js'), static::$privateKey . Settings::getHashSalt());
    $this->assertSame('//cdn.example.com/cdn/ff/' . $drupal_js_security_token . '/' . $drupal_js_mtime . '/:relative:/core/misc/drupal.js', $gen->generateString('core/misc/drupal.js'));
    // Since the public stream wrapper is not available in the unit test,
    // and we use file_exists() in the target method, we are using the
    // file:// scheme that ships with PHP. This does require
    // injecting a leading into the path that we compare against, to match
    // the method.
    $llama_jpg_security_token = Crypt::hmacBase64($llama_jpg_mtime . 'file' . UrlHelper::encodePath('/' . $llama_jpg_filepath), static::$privateKey . Settings::getHashSalt());
    $this->assertSame('//cdn.example.com/cdn/ff/' . $llama_jpg_security_token . '/' . $llama_jpg_mtime . '/file/' . UrlHelper::encodePath($llama_jpg_filepath), $gen->generateString('file://' . $llama_jpg_filepath));

    // In subdir: 1) non-existing file, 2) shipped file, 3) managed file.
    $gen = $this->createFileUrlGenerator('/subdir', $config);
    $this->assertSame('//cdn.example.com/subdir/core/misc/does-not-exist.js', $gen->generateString('core/misc/does-not-exist.js'));
    $this->assertSame('//cdn.example.com/subdir/cdn/ff/' . $drupal_js_security_token . '/' . $drupal_js_mtime . '/:relative:/core/misc/drupal.js', $gen->generateString('core/misc/drupal.js'));
    $this->assertSame('//cdn.example.com/subdir/cdn/ff/' . $llama_jpg_security_token . '/' . $llama_jpg_mtime . '/file/' . UrlHelper::encodePath($llama_jpg_filepath), $gen->generateString('file://' . $llama_jpg_filepath));

    unlink($llama_jpg_filepath);
  }

  /**
   * Creates a FileUrlGenerator with mostly dummies.
   *
   * @param string $base_path
   *   The base path to let Request::getBasePath() return.
   * @param array $raw_config
   *   The raw config for the cdn.settings.yml config.
   *
   * @return \Drupal\cdn\File\FileUrlGenerator
   *   The FileUrlGenerator to test.
   */
  protected function createFileUrlGenerator($base_path, array $raw_config) {
    $core_file_url_generator = $this->prophesize(CoreFileUrlGenerator::class);
    $core_file_url_generator->generate(Argument::type('string'))
      ->willReturn(Url::fromUri(static::DECORATED_SERVICE_RETURNED_URL));
    $core_file_url_generator->generateString(Argument::type('string'))
      ->willReturn(static::DECORATED_SERVICE_RETURNED_URL);
    $core_file_url_generator->generateAbsoluteString(Argument::type('string'))
      ->willReturn(static::DECORATED_SERVICE_RETURNED_URL);

    $request = $this->prophesize(Request::class);
    $request->getBasePath()
      ->willReturn($base_path);
    $request->getSchemeAndHttpHost()
      ->willReturn('http://example.com');
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getCurrentRequest()
      ->willReturn($request->reveal());

    // @todo make this more elegant: the current URI is normally stored on the
    //   PublicStream instance, but while it is prophesized, that does not seem
    //   possible.
    $current_uri = '';

    $public_stream_wrapper = $this->prophesize(PublicStream::class);
    $public_stream_wrapper->getExternalUrl()
      ->will(function () use ($base_path, &$current_uri) {
        return 'http://example.com' . $base_path . '/sites/default/files/' . UrlHelper::encodePath(substr($current_uri, 9));
      });
    $file_stream_wrapper = $this->prophesize(LocalStream::class);
    $root = $this->root;
    $file_stream_wrapper->getExternalUrl()
      ->will(function () use ($root, $base_path, &$current_uri) {
        // The file:// stream wrapper is only used for testing FF.
        return 'http://example.com/inaccessible';
      });
    $stream_wrapper_manager = $this->prophesize(StreamWrapperManagerInterface::class);
    $stream_wrapper_manager->getWrappers(StreamWrapperInterface::LOCAL_NORMAL)
      ->willReturn(['public' => TRUE]);
    $stream_wrapper_manager->getViaUri(Argument::that(function ($uri) {
      return substr($uri, 0, 9) === 'public://';
    }))
      ->will(function ($args) use (&$public_stream_wrapper, &$current_uri) {
        $s = $public_stream_wrapper->reveal();
        $current_uri = $args[0];
        return $s;
      });
    $stream_wrapper_manager->getViaUri(Argument::that(function ($uri) {
      return substr($uri, 0, 7) === 'file://';
    }))
      ->will(function ($args) use (&$file_stream_wrapper, &$current_uri) {
        $s = $file_stream_wrapper->reveal();
        $current_uri = $args[0];
        return $s;
      });
    $private_key = $this->prophesize(PrivateKey::class);
    $private_key->get()
      ->willReturn(static::$privateKey);

    return new FileUrlGenerator(
      $core_file_url_generator->reveal(),
      $this->root,
      $stream_wrapper_manager->reveal(),
      $request_stack->reveal(),
      $private_key->reveal(),
      new CdnSettings($this->getConfigFactoryStub(['cdn.settings' => $raw_config]), $stream_wrapper_manager->reveal())
    );
  }

}
