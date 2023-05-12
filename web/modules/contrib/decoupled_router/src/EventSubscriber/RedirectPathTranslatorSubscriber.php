<?php

namespace Drupal\decoupled_router\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\decoupled_router\PathTranslatorEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event subscriber that processes a path translation with the redirect info.
 */
class RedirectPathTranslatorSubscriber extends RouterPathTranslatorSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We wanna run before the router-based path translator because redirects
    // naturally act before routing subsystem in Drupal HTTP kernel.
    $events[PathTranslatorEvent::TRANSLATE][] = ['onPathTranslation', 10];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onPathTranslation(PathTranslatorEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof CacheableJsonResponse) {
      $this->logger->error('Unable to get the response object for the decoupled router event.');
      return;
    }
    if (!$this->moduleHandler->moduleExists('redirect')) {
      return;
    }
    // Find the redirected path. Bear in mind that we need to go through several
    // redirection levels before handing off to the route translator.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $redirect_storage = $entity_type_manager->getStorage('redirect');
    $destination = parse_url($event->getPath(), PHP_URL_PATH);
    $original_query_string = parse_url($event->getPath(), PHP_URL_QUERY);
    $traced_urls = [];
    $redirect = NULL;
    $redirects_trace = [];
    while (TRUE) {
      $destination = $this->cleanSubdirInPath($destination, $event->getRequest());
      $path_without_prefix = $destination;
      $langcodes = [];
      if ($this->languageManager->isMultilingual()) {
        $langcodes = [LanguageInterface::LANGCODE_NOT_SPECIFIED];
        $language_negotiation_url = $this->languageManager->getNegotiator()
          ->getNegotiationMethodInstance('language-url');
        $router_request = Request::create($destination);
        $langcode = $language_negotiation_url->getLangcode($router_request);
        $language_prefixes = $this->configFactory->get('language.negotiation')->get('url.prefixes');
        $lang_prefix = $language_prefixes[$langcode] ?? '';
        if ($langcode && ($destination === "/$lang_prefix" || strpos($destination, "/$lang_prefix/") === 0)) {
          $langcodes[] = $langcode;
          $path_without_prefix = $language_negotiation_url->processInbound($destination, $router_request);
        }
      }
      // Find if there is a redirect for this path.
      $query = $redirect_storage
        ->getQuery()
        ->accessCheck(TRUE)
        // Redirects are stored without the leading slash :-(.
        ->condition('redirect_source__path', ltrim($path_without_prefix, '/'));
      if (!empty($langcodes)) {
        $query->condition('language', $langcodes, 'IN');
      }
      $results = $query->execute();
      $rid = reset($results);
      if (!$rid) {
        break;
      }
      /** @var \Drupal\redirect\Entity\Redirect $redirect */
      $redirect = $redirect_storage->load($rid);
      $response->addCacheableDependency($redirect);
      $uri = $redirect->get('redirect_redirect')->uri;
      $url = Url::fromUri($uri)->toString(TRUE);
      $redirects_trace[] = [
        'from' => $this->makeRedirectUrl($destination, $original_query_string),
        'to' => $this->makeRedirectUrl($url->getGeneratedUrl(), $original_query_string),
        'status' => $redirect->getStatusCode(),
      ];
      $destination = $url->getGeneratedUrl();

      // Detect infinite loops and break if there is one.
      $infinite_loop = in_array($destination, array_map(function (GeneratedUrl $url) {
        return $url->getGeneratedUrl();
      }, $traced_urls));
      // Accumulate all the URLs we go through to add the necessary cacheability
      // metadata at the end.
      $traced_urls[] = $url;
      if ($infinite_loop) {
        break;
      }
    }
    if (!$redirect) {
      return;
    }
    // At this point we should be pointing to a system route or path alias.
    $event->setPath($this->makeRedirectUrl($destination, $original_query_string));

    // Now call the route level.
    parent::onPathTranslation($event);

    if (!$response->isSuccessful()) {
      return;
    }
    // Set the content in the response.
    $content = Json::decode($response->getContent());
    $response->setData(array_merge(
      $content,
      ['redirect' => $redirects_trace]
    ));
    // If there is a response object, add the cacheability metadata necessary
    // for the traced URLs.
    array_walk($traced_urls, function ($traced_url) use ($response) {
      $response->addCacheableDependency($traced_url);
    });
    $event->stopPropagation();
  }

  /**
   * Generates URL for the redirect, based on redirect module configurations.
   *
   * @param string $path
   *   URL to redirect to.
   * @param string $query
   *   Original query string on the requested path.
   *
   * @return string
   *   Redirect URL to use.
   */
  private function makeRedirectUrl($path, $query) {
    return $query && $this->configFactory->get('redirect.settings')
      ->get('passthrough_querystring')
      ? "{$path}?{$query}"
      : $path;
  }

}
