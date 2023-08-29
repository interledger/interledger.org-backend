<?php

namespace Drupal\cdn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A CDN scheme.
 *
 * @Constraint(
 *   id = "CdnScheme",
 *   label = @Translation("CDN scheme", context = "Validation"),
 * )
 *
 * A scheme or a network-path reference as defined in RFC3986.
 * (A network-path reference is more commonly known as a "protocol-relative URL"
 * or a "scheme-relative URL".)
 * @see https://tools.ietf.org/html/rfc3986#section-3.1
 * @see https://tools.ietf.org/html/rfc3986#section-4.2
 */
class CdnSchemeConstraint extends Constraint {

  public $message = 'The provided scheme %scheme is not valid. Provide a scheme like <samp>http://</samp>, or <samp>https://</samp> or to use scheme-relative URLs, use <samp>//</samp>.';

}
