<?php

declare(strict_types = 1);

namespace Drupal\cdn;

use Drupal\cdn\File\FileUrlGenerator;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CdnFarfutureController {

  /**
   * @param \Drupal\Core\PrivateKey $privateKey
   *   The private key service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   */
  public function __construct(
    protected PrivateKey $privateKey,
    protected StreamWrapperManagerInterface $streamWrapperManager
  ) {}

  /**
   * Serves the requested file with optimal far future expiration headers.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request. $request->query must have relative_file_url, set by
   *   \Drupal\cdn\PathProcessor\CdnFarfuturePathProcessor.
   * @param string $security_token
   *   The security token. Ensures that users can not request any file they want
   *   by manipulating the URL (they could otherwise request settings.php for
   *   example). See https://www.drupal.org/node/1441502.
   * @param int $mtime
   *   The file's mtime.
   * @param string $scheme
   *   The file's scheme.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The response that will efficiently send the requested file.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the 'relative_file_url' query argument is not set, which can
   *   only happen in case of malicious requests or in case of a malfunction in
   *   \Drupal\cdn\PathProcessor\CdnFarfuturePathProcessor.
   */
  public function download(Request $request, string $security_token, int $mtime, string $scheme) : Response {
    // Validate the scheme early.
    if (!$request->query->has('relative_file_url') || ($scheme !== FileUrlGenerator::RELATIVE && !$this->streamWrapperManager->isValidScheme($scheme))) {
      throw new BadRequestHttpException();
    }

    // Validate security token.
    $relative_file_url = $request->query->get('relative_file_url');
    $calculated_token = Crypt::hmacBase64($mtime . $scheme . $relative_file_url, $this->privateKey->get() . Settings::getHashSalt());
    if ($security_token !== $calculated_token) {
      return new Response('Invalid security token.', 403);
    }

    // A relative URL for a file contains '%20' instead of spaces. A relative
    // file path contains spaces.
    $relative_file_path = rawurldecode($relative_file_url);

    $file_to_stream = $scheme === FileUrlGenerator::RELATIVE
      ? substr($relative_file_path, 1)
      : $scheme . '://' . substr($relative_file_path, 1);

    $response = new BinaryFileResponse($file_to_stream, 200, $this->getFarfutureHeaders(), TRUE, NULL, FALSE, FALSE);
    $response->isNotModified($request);
    // @todo Remove once the CDN module requires a version of Drupal core that includes https://www.drupal.org/project/drupal/issues/3172550.
    if ($mime_type = \Drupal::service('file.mime_type.guesser')->guessMimeType($relative_file_path)) {
      $response->headers->set('Content-Type', $mime_type);
    }
    return $response;
  }

  /**
   * Return the headers to serve with far future responses.
   *
   * @return string[]
   */
  protected function getFarfutureHeaders() : array {
    return [
      // Instead of being powered by PHP, tell the world this resource was
      // powered by the CDN module!
      'X-Powered-By' => 'Drupal CDN module (https://www.drupal.org/project/cdn)',
      // Browsers that implement the W3C Access Control specification might
      // refuse to use certain resources such as fonts if those resources
      // violate the same-origin policy. Send a header to explicitly allow
      // cross-domain use of those resources. (This is called Cross-Origin
      // Resource Sharing, or CORS.)
      // The CDN module allows any domain to access it by default, which means
      // hotlinking of these files is possible. If you want to prevent this,
      // implement a KernelEvents::RESPONSE subscriber that modifies this header
      // for this route.
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET, HEAD',
      // Set a far future Cache-Control header (480 weeks), and allows any
      // intermediate cache to cache it, since it's marked as a public resource.
      // Finally, it's also marked as "immutable", which helps avoid
      // revalidation, see:
      // - https://bitsup.blogspot.be/2016/05/cache-control-immutable.html
      // - https://tools.ietf.org/html/rfc8246
      'Cache-Control' => 'max-age=290304000, public, immutable',
      // Set a far future Expires header. The maximum UNIX timestamp is
      // somewhere in 2038. Set it to a date in 2037, just to be safe.
      'Expires' => 'Tue, 20 Jan 2037 04:20:42 GMT',
      // Pretend the file was last modified a long time ago in the past, this
      // will prevent browsers that don't support Cache-Control nor Expires
      // headers to still request a new version too soon (these browsers
      // calculate a heuristic to determine when to request a new version, based
      // on the last time the resource has been modified).
      // Also see http://code.google.com/speed/page-speed/docs/caching.html.
      'Last-Modified' => 'Wed, 20 Jan 1988 04:20:42 GMT',
    ];
  }

}
