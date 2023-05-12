<?php

namespace Drupal\Tests\subrequests\Normalizer;

use Drupal\subrequests\Normalizer\MultiresponseNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\subrequests\Normalizer\MultiresponseNormalizer
 * @group subrequests
 */
class MultiresponseNormalizerTest extends UnitTestCase {

  /**
   * @var \Drupal\subrequests\Normalizer\MultiresponseNormalizer
   */
  protected $sut;

  protected function setUp(): void {
    parent::setUp();
    $this->sut = new MultiresponseNormalizer();
  }

  /**
   * @dataProvider dataProviderSupportsNormalization
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization($data, $format, $is_supported) {
    $actual = $this->sut->supportsNormalization($data, $format);
    $this->assertSame($is_supported, $actual);
  }

  public function dataProviderSupportsNormalization() {
    return [
      [[new Response('')], 'multipart-related', TRUE],
      [[], 'multipart-related', TRUE],
      [[new Response('')], 'fail', FALSE],
      [NULL, 'multipart-related', FALSE],
      [[new Response(''), NULL], 'multipart-related', FALSE],
    ];
  }

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $sub_content_type = $this->getRandomGenerator()->string();
    $data = [new Response('Foo!'), new Response('Bar')];
    $actual = $this->sut->normalize($data, NULL, ['sub-content-type' => $sub_content_type]);
    $parts = explode('; ', $actual['headers']['Content-Type']);
    parse_str($parts[1], $parts);
    $delimiter = substr($parts['boundary'], 1, strlen($parts['boundary']) - 2);
    $this->assertStringStartsWith('--' . $delimiter, $actual['content']);
    $this->assertStringEndsWith('--' . $delimiter . '--', $actual['content']);
    $this->assertMatchesRegularExpression("/\r\nFoo!\r\n/", $actual['content']);
    $this->assertMatchesRegularExpression("/\r\nBar\r\n/", $actual['content']);
  }

}
