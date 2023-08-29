<?php

declare(strict_types = 1);

namespace Drupal\cdn\File;

use Drupal\cdn\CdnSettings;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generates CDN file URLs.
 *
 * @see https://www.drupal.org/node/2669074
 */
class FileUrlGenerator implements FileUrlGeneratorInterface {

  final public const RELATIVE = ':relative:';

  /**
   * Constructs a new CDN file URL generator object.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $decorated
   *   The decorated file URL generator.
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\PrivateKey $privateKey
   *   The private key service.
   * @param \Drupal\cdn\CdnSettings $settings
   *   The CDN settings service.
   */
  public function __construct(
    protected FileUrlGeneratorInterface $decorated,
    protected readonly string $root,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected RequestStack $requestStack,
    protected PrivateKey $privateKey,
    protected CdnSettings $settings
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generateString(string $uri): string {
    return $this->doGenerate($uri) ?? $this->decorated->generateString($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function generateAbsoluteString(string $uri): string {
    return $this->doGenerate($uri) ?? $this->decorated->generateAbsoluteString($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $uri): Url {
    $result = $this->doGenerate($uri);
    return $result ? Url::fromUri($result) : $this->decorated->generate($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function transformRelative(string $file_url, bool $root_relative = TRUE): string {
    return $this->decorated->transformRelative($file_url, $root_relative);
  }

  /**
   * Generates a CDN file URL for local files that are mapped to a CDN.
   *
   * Compatibility: normal paths and stream wrappers.
   *
   * There are two kinds of local files:
   * - "managed files", i.e. those stored by a Drupal-compatible stream wrapper.
   *   These are files that have either been uploaded by users or were generated
   *   automatically (for example through CSS aggregation).
   * - "shipped files", i.e. those outside of the files directory, which ship as
   *   part of Drupal core or contributed modules or themes.
   *
   * @param string $uri
   *   The URI to a file for which we need a CDN URL, or the path to a shipped
   *   file.
   *
   * @return string|null
   *   A string containing the scheme-relative CDN file URI, or NULL if this
   *   file URI should not be served from a CDN.
   */
  public function doGenerate(string $uri): ?string {
    // Don't alter file URLs when running update.php.
    // @todo Remove the second condition after the CDN module requires the Drupal core minor that ships with https://www.drupal.org/project/drupal/issues/2969056
    if (defined('MAINTENANCE_MODE') || stripos($_SERVER['PHP_SELF'], 'update.php') !== FALSE) {
      return NULL;
    }

    // Don't alter CSS file URLs while settings.php is disabling CSS
    // aggregation.
    if (substr($uri, -4) === '.css' && isset($GLOBALS['config']['system.performance']['css']['preprocess']) && $GLOBALS['config']['system.performance']['css']['preprocess'] === FALSE) {
      return NULL;
    }

    // Don't alter file URLs while processing a CSS file.
    // @see \Drupal\cdn\Asset\CssOptimizer
    global $_cdn_in_css_file;
    if ($_cdn_in_css_file) {
      return NULL;
    }

    if (!$this->settings->isEnabled()) {
      return NULL;
    }

    if (!$this->canServe($uri)) {
      return NULL;
    }

    // Don't serve CKEditor from a CDN when far future future is enabled
    // (CKEditor insists on computing other assets to load based on this URL).
    if ($uri === 'core/assets/vendor/ckeditor/ckeditor.js' && $this->settings->farfutureIsEnabled()) {
      return NULL;
    }

    $cdn_domain = $this->getCdnDomain($uri);
    if ($cdn_domain === FALSE) {
      return NULL;
    }

    if (!$scheme = StreamWrapperManager::getScheme($uri)) {
      $scheme = self::RELATIVE;
      $relative_url = '/' . $uri;
      $relative_file_path = $relative_url;
      $absolute_file_path = $this->root . $relative_url;
    }
    else {
      $relative_url = str_replace($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $this->getBasePath(), '', $this->streamWrapperManager->getViaUri($uri)->getExternalUrl());
      $relative_file_path = '/' . substr($uri, strlen($scheme . '://'));
      $absolute_file_path = $scheme . '://' . $relative_file_path;
    }

    // When farfuture is enabled, rewrite the file URL to let Drupal serve the
    // file with optimal headers. Only possible if the file exists.
    if ($this->settings->farfutureIsEnabled() && file_exists($absolute_file_path)) {
      // We do the filemtime() call separately, because a failed filemtime()
      // will cause a PHP warning to be written to the log, which would remove
      // any performance gain achieved by removing the file_exists() call.
      $mtime = filemtime($absolute_file_path);

      // Generate a security token. Ensures that users can not request any
      // file they want by manipulating the URL (they could otherwise request
      // settings.php for example). See https://www.drupal.org/node/1441502.
      $calculated_token = Crypt::hmacBase64($mtime . $scheme . UrlHelper::encodePath($relative_file_path), $this->privateKey->get() . Settings::getHashSalt());
      return $this->settings->getScheme() . $cdn_domain . $this->getBasePath() . '/cdn/ff/' . $calculated_token . '/' . $mtime . '/' . $scheme . UrlHelper::encodePath($relative_file_path);
    }

    return $this->settings->getScheme() . $cdn_domain . $this->getBasePath() . $relative_url;
  }

  /**
   * Gets the CDN domain to use for the given file URI.
   *
   * @param string $uri
   *   The URI to a file for which we need a CDN URL, or the path to a shipped
   *   file.
   *
   * @return bool|string
   *   Returns FALSE if the URI has an extension is not configured to be served
   *   from a CDN. Otherwise, returns a CDN domain.
   */
  protected function getCdnDomain(string $uri) {
    // Extension-specific mapping. Make sure file extension has any querystring
    // or fragment removed before checking the lookup table.
    $file_extension = preg_split('/[?#]/', mb_strtolower(pathinfo($uri, PATHINFO_EXTENSION)))[0];
    $lookup_table = $this->settings->getLookupTable();
    if (isset($lookup_table[$file_extension])) {
      $key = $file_extension;
    }
    // Generic or fallback mapping.
    elseif (isset($lookup_table['*'])) {
      $key = '*';
    }
    // No mapping.
    else {
      return FALSE;
    }

    $result = $lookup_table[$key];

    if ($result === FALSE) {
      return FALSE;
    }
    // If there are multiple results, pick one using consistent hashing: ensure
    // the same file is always served from the same CDN domain.
    elseif (is_array($result)) {
      $filename = basename($uri);
      $hash = hexdec(substr(md5($filename), 0, 5));
      return $result[$hash % count($result)];
    }
    else {
      return $result;
    }
  }

  /**
   * Determines if a URI can/should be served by CDN.
   *
   * @param string $uri
   *   The URI to a file for which we need a CDN URL, or the path to a shipped
   *   file.
   *
   * @return bool
   *   Returns FALSE if the URI is not for a shipped file or in an eligible
   *   stream. TRUE otherwise.
   */
  protected function canServe(string $uri) : bool {
    $scheme = StreamWrapperManager::getScheme($uri);

    // Allow additional stream wrappers to be served via CDN.
    $allowed_stream_wrappers = $this->settings->getStreamWrappers();
    // If the URI is absolute — HTTP(S) or otherwise — return early, except if
    // it's an absolute URI using an allowed stream wrapper.
    if ($scheme && !in_array($scheme, $allowed_stream_wrappers, TRUE)) {
      return FALSE;
    }
    // If the URI is scheme-relative, return early.
    elseif (mb_substr($uri, 0, 2) === '//') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @see \Symfony\Component\HttpFoundation\Request::getBasePath()
   */
  protected function getBasePath() : string {
    return $this->requestStack->getCurrentRequest()->getBasePath();
  }

}
