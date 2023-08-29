<?php

declare(strict_types = 1);

namespace Drupal\cdn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CDN-supported stream wrapper constraint validator.
 */
class CdnStreamWrapperConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($stream_wrapper, Constraint $constraint) {
    if (!$constraint instanceof CdnStreamWrapperConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CdnStreamWrapper');
    }

    if ($stream_wrapper === NULL) {
      return;
    }

    if (!static::isValidCdnStreamWrapper($stream_wrapper)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%stream_wrapper', $stream_wrapper)
        ->setInvalidValue($stream_wrapper)
        ->addViolation();
    }
  }

  /**
   * Validates the given stream wrapper, with an exception for "private".
   *
   * @param string $stream_wrapper
   *   A stream wrapper configured for use in Drupal.
   *
   * @return bool
   */
  protected static function isValidCdnStreamWrapper(string $stream_wrapper) : bool {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $forbidden_wrappers = ['private'];
    return !in_array($stream_wrapper, $forbidden_wrappers, TRUE)
      && $stream_wrapper_manager->getClass($stream_wrapper) !== FALSE;
  }

}
