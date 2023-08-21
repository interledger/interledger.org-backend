<?php

declare(strict_types = 1);

namespace Drupal\cdn\StackMiddleware;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Redirects CDN user agents' requests for HTML to the canonical location.
 *
 * Prevents the CDN from returning content (HTML pages). We only want the CDN to
 * serve files like images, CSS, JavaScript, et cetera. Without this, it will
 * return anything.
 *
 * This prevents that infamous "duplicate content" SEO problem by redirecting
 * when appropriate.
 *
 * Of course, the web servers will only execute Drupal for URLs
 * that don't have a file on disk. So this code is only executed for actual
 * responses Drupal needs to send … but also for generated files, such as
 * image styles.
 * So, this code assumes that URLs without file extensions (or HTML-like file
 * extensions) is for HTML responses. Anything else is assumed to be for
 * generated files, such as image styles.
 *
 * However, a consequence of this is that if your site is behind a reverse proxy
 * (such as Varnish) that indiscriminately caches responses for anonymous users,
 * that your reverse proxy will end up serving responses intended only for CDN
 * user agents to non-CDN user agents. Consequently, your end users will be
 * stuck in an endless redirect loop.
 *
 * Ideally, the CDN module would cause Drupal 8 to send a 'Vary: User-Agent'
 * response header for every response. But since that means caching thousands of
 * variations, it implies it would be effectively disabling caching in the
 * reverse proxy, which is undesirable. There we have to choose between two
 * strategies:
 * 1. Duplicate the logic of this class for the reverse proxy.
 * 2. Ensure that responses to requests made by the CDN user agent are cached
 *    *separately* by the reverse proxy. This effectively implements a very
 *    limited 'Vary: User-Agent'. (In other words: let your reverse proxy
 *    normalize the 'User-Agent' header into two categories: CDN user agent and
 *    everything else. Then use this normalized value as a key for looking up
 *    cached responses.)
 *
 * You may wonder why this doesn't impact Drupal's built-in Page Cache. Well,
 * this middleware runs *before* the Page Cache. So it's effectively identical
 * to strategy 1.
 *
 * @see https://www.drupal.org/node/2678374#comment-11278435
 * @see https://www.varnish-cache.org/docs/3.0/tutorial/vary.html#pitfall-vary-user-agent
 */
class DuplicateContentPreventionMiddleware implements HttpKernelInterface {

  /**
   * The file extensions for which this middleware will act.
   *
   * Hardcoded to avoid costly I/O.
   *
   * @var string[]
   */
  protected array $forbiddenExtensions = ['', 'html', 'htm', 'php'];

  /**
   * The CDN user agents.
   *
   * Hardcoded to avoid costly I/O.
   *
   * @var string[]
   * @note To add more CDN user agents, file a feature request at https://www.drupal.org/node/add/project-issue/cdn
   */
  protected array $cdnUserAgents = ['amazon cloudfront', 'akamai'];

  /**
   * The request headers only set by CDN user agents.
   *
   * Hardcoded to avoid costly I/O.
   *
   * @var string[]
   * @note To add more CDN-only request headers, file a feature request at https://www.drupal.org/node/add/project-issue/cdn
   */
  protected array $cdnOnlyRequestHeaders = ['CF-RAY'];

  /**
   * Constructs a DuplicateContentPreventionMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The decorated kernel.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected RequestStack $requestStack
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE): Response {
    $redirect_url = $this->getRedirectUrl($request);
    if ($redirect_url) {
      return new RedirectResponse($redirect_url, 301, [
        // @see http://googlewebmastercentral.blogspot.com/2011/06/supporting-relcanonical-http-headers.html
        'Link' => '<' . $redirect_url . '>; rel="canonical"',
        // Informative header to simplify debugging.
        'Drupal-CDN-Redirect' => 'duplicate content prevention',
      ]);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Determines whether a redirect should be performed for the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool|string
   *   FALSE if no redirect should occur, or the absolute URL to redirect to.
   */
  protected function getRedirectUrl(Request $request) {
    $path = $request->getPathInfo();

    // If the path ends in an extension that is not in the list of forbidden
    // extensions, then return FALSE to indicate that no redirect should occur.
    // We cannot assume that this can only happen inside the /sites directory,
    // because a Drupal 8 site can choose to use a different directory for
    // generated files. We use a blacklist rather than a whitelist of extensions
    // to ensure that any current and future files can be served.
    $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, $this->forbiddenExtensions)) {
      return FALSE;
    }

    assert(Inspector::assertAllStrings($this->cdnOnlyRequestHeaders), 'CDN-only request headers must be strings.');
    foreach ($this->cdnOnlyRequestHeaders as $cdn_only_request_header) {
      if ($request->headers->has($cdn_only_request_header)) {
        return $this->generateRedirectUrl($request);
      }
    }

    // Use case-insensitive substring matching to match the current User-Agent
    // to the list of CDN user agents.
    if ($request->headers->has('User-Agent')) {
      $ua = mb_strtolower($request->headers->get('User-Agent'));
      assert(Inspector::assertAllStrings($this->cdnUserAgents), 'CDN user agents must be strings.');
      assert(Inspector::assertAll(function($s) { return mb_strtolower($s) === $s; }, $this->cdnUserAgents), 'CDN user agents must be lower case strings.'); // @codingStandardsIgnoreLine
      foreach ($this->cdnUserAgents as $cdn_ua) {
        if (strstr($ua, $cdn_ua)) {
          return $this->generateRedirectUrl($request);
        }
      }
    }

    return FALSE;
  }

  /**
   * Generates a redirect URL for the given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request.
   *
   * @return string
   *   The URL for the request.
   */
  protected function generateRedirectUrl(Request $request) : string {
    // Put the current request on the stack. We have to do this manually
    // because this runs so early that the request stack is still empty.
    // @see \Drupal\Core\StackMiddleware\KernelPreHandle::handle()
    // @see \Drupal\Core\DrupalKernel::preHandle()
    $this->requestStack->push($request);

    return Url::fromUri('base:' . $request->getPathInfo())->setAbsolute(TRUE)->toString(FALSE);
  }

}
