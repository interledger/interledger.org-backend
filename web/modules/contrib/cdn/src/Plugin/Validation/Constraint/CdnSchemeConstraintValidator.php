<?php

declare(strict_types = 1);

namespace Drupal\cdn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CDN scheme constraint validator.
 */
class CdnSchemeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($scheme, Constraint $constraint) {
    if (!$constraint instanceof CdnSchemeConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CdnScheme');
    }

    if (!static::isValidCdnScheme($scheme)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%scheme', $scheme)
        ->setInvalidValue($scheme)
        ->addViolation();
    }
  }

  /**
   * Validates the given CDN scheme.
   *
   * @param string $scheme
   *   A scheme as expected by the CDN module: `//`, `https://` or `http://`.
   *
   * @return bool
   */
  protected static function isValidCdnScheme(string $scheme) : bool {
    return in_array($scheme, ['https://', 'http://', '//'], TRUE);
  }

}
