<?php

declare(strict_types = 1);

namespace Drupal\Tests\cdn\Unit\Plugin\Validation\Constraint;

use Drupal\cdn\Plugin\Validation\Constraint\CdnSchemeConstraint;
use Drupal\cdn\Plugin\Validation\Constraint\CdnSchemeConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \Drupal\cdn\Plugin\Validation\Constraint\CdnSchemeConstraintValidator
 * @group cdn
 */
class CdnSchemeConstraintValidatorTest extends UnitTestCase {

  /**
   * @covers ::validate
   *
   * @dataProvider provideTestValidate
   */
  public function testValidate($value, $valid) {
    $constraint_violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
    $constraint_violation_builder->setParameter('%scheme', $value)
      ->willReturn($constraint_violation_builder->reveal());
    $constraint_violation_builder->setInvalidValue($value)
      ->willReturn($constraint_violation_builder->reveal());
    $constraint_violation_builder->addViolation()
      ->willReturn($constraint_violation_builder->reveal());
    if ($valid) {
      $constraint_violation_builder->addViolation()->shouldNotBeCalled();
    }
    else {
      $constraint_violation_builder->addViolation()->shouldBeCalled();
    }
    $context = $this->prophesize(ExecutionContextInterface::class);
    $context->buildViolation(Argument::type('string'))
      ->willReturn($constraint_violation_builder->reveal());

    $constraint = new CdnSchemeConstraint();

    $validate = new CdnSchemeConstraintValidator();
    $validate->initialize($context->reveal());
    $validate->validate($value, $constraint);
  }

  public function provideTestValidate() {
    $data = [];

    // Valid schemes.
    $data['http://'] = ['http://', TRUE];
    $data['https://'] = ['https://', TRUE];
    $data['//'] = ['//', TRUE];

    // Scheme without `://`.
    $data['https'] = ['https', FALSE];
    $data['https:'] = ['https:', FALSE];
    $data['https:/'] = ['https:/', FALSE];

    // Disallowed schemes.
    $data['ftp://'] = ['ftp://', FALSE];
    $data['something://'] = ['ftp://', FALSE];

    // Non-scheme values.
    $data['/'] = ['/', FALSE];
    $data[''] = ['', FALSE];

    return $data;
  }

}
