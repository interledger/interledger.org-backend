<?php

declare(strict_types = 1);

namespace Drupal\Tests\cdn\Kernel;

use Drupal\cdn\Plugin\Validation\Constraint\CdnStreamWrapperConstraint;
use Drupal\cdn\Plugin\Validation\Constraint\CdnStreamWrapperConstraintValidator;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \Drupal\cdn\Plugin\Validation\Constraint\CdnStreamWrapperConstraintValidator
 * @group cdn
 */
class CdnStreamWrapperConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test', 'file'];

  /**
   * @covers ::validate
   *
   * @dataProvider provideTestValidate
   */
  public function testValidate($value, $valid) {
    $constraint_violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
    $constraint_violation_builder->setParameter('%stream_wrapper', $value)
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

    $constraint = new CdnStreamWrapperConstraint();

    $validate = new CdnStreamWrapperConstraintValidator();
    $validate->initialize($context->reveal());
    $validate->validate($value, $constraint);
  }

  public function provideTestValidate() {
    $data = [];

    $data['public, registered by default'] = ['public', TRUE];
    $data['private, prohibited by rule'] = ['private', FALSE];
    $data['unregistered'] = ['unregistered', FALSE];
    $data['custom, provided by test module'] = ['dummy-remote', TRUE];

    return $data;
  }

}
