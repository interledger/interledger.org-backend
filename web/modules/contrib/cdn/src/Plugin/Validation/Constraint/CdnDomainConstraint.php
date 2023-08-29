<?php

namespace Drupal\cdn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A CDN domain.
 *
 * @Constraint(
 *   id = "CdnDomain",
 *   label = @Translation("CDN domain", context = "Validation"),
 * )
 *
 * An authority as defined in RFC3986. An authority consists of host, optional
 * userinfo and optional port. The host can be an IP address or registered
 * domain name.
 * @see https://tools.ietf.org/html/rfc3986#section-3.2
 */
class CdnDomainConstraint extends Constraint {

  public $message = 'The provided domain %domain is not valid. Provide a hostname like <samp>cdn.com</samp> or <samp>cdn.example.com</samp>. IP addresses and ports are also allowed.';

}
