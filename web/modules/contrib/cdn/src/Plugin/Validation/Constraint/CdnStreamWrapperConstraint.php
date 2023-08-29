<?php

namespace Drupal\cdn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A CDN-supported stream wrapper.
 *
 * @Constraint(
 *   id = "CdnStreamWrapper",
 *   label = @Translation("CDN stream wrapper", context = "Validation"),
 * )
 *
 * A stream wrapper as registered through Drupal's file system API.
 */
class CdnStreamWrapperConstraint extends Constraint {

  public $message = 'The provided stream wrapper %stream_wrapper is not valid. Ensure it is properly registered and is not \'private\'.';

}
