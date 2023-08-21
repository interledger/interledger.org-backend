<?php

declare(strict_types = 1);

namespace Drupal\Tests\cdn\Unit\Plugin\Validation\Constraint;

use Drupal\cdn\Plugin\Validation\Constraint\CdnDomainConstraint;
use Drupal\cdn\Plugin\Validation\Constraint\CdnDomainConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \Drupal\cdn\Plugin\Validation\Constraint\CdnDomainConstraintValidator
 * @group cdn
 */
class CdnDomainConstraintValidatorTest extends UnitTestCase {

  /**
   * @covers ::validate
   *
   * @dataProvider provideTestValidate
   */
  public function testValidate($value, $valid) {
    $constraint_violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
    $constraint_violation_builder->setParameter('%domain', $value)
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

    $constraint = new CdnDomainConstraint();

    $validate = new CdnDomainConstraintValidator();
    $validate->initialize($context->reveal());
    $validate->validate($value, $constraint);
  }

  public function provideTestValidate() {
    // phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
    $data = [];

    $data['NULL is allowed because this is the initial value when installing the CDN module'] = [NULL, TRUE];

    // Host = domain.
    $data['host (domain) '] = ['cdn', TRUE];
    $data['userinfo + host (domain)'] = ['user:pass@cdn', TRUE];
    $data['host (domain) + port'] = ['cdn:1988', TRUE];
    $data['userinfo + host (domain) + port'] = ['user:pass@cdn:1988', TRUE];

    $data['host (domain) + path'] = ['cdn/foo/bar', FALSE];
    $data['host (domain) + query'] = ['cdn?foo=bar', FALSE];
    $data['host (domain) + fragment'] = ['cdn#foobar', FALSE];
    $data['host (domain) + path + query'] = ['cdn/foo/bar?foo=bar', FALSE];
    $data['host (domain) + path + fragment'] = ['cdn/foo/bar#foobar', FALSE];
    $data['host (domain) + path + query + fragment'] = ['cdn/foo/bar?foo=bar#foobar', FALSE];
    $data['host (domain) + query + fragment'] = ['cdn/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme-relative + host (domain)'] = ['//cdn', FALSE];

    $data['scheme + host (domain)'] = ['https://cdn', FALSE];
    $data['scheme + host (domain) + path'] = ['https://cdn/foobar', FALSE];
    $data['scheme + host (domain) + query'] = ['https://cdn?foo=bar', FALSE];
    $data['scheme + host (domain) + fragment'] = ['https://cdn#foobar', FALSE];
    $data['scheme + host (domain) + path + query'] = ['https://cdn/foo/bar?foo=bar', FALSE];
    $data['scheme + host (domain) + path + fragment'] = ['https://cdn/foo/bar#foobar', FALSE];
    $data['scheme + host (domain) + path + query + fragment'] = ['https://cdn/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (domain) + query + fragment'] = ['https://cdn?foo=bar#foobar', FALSE];

    $data['userinfo + host (domain) + path'] = ['user:pass@cdn/foo/bar', FALSE];
    $data['userinfo + host (domain) + query'] = ['user:pass@cdn?foo=bar', FALSE];
    $data['userinfo + host (domain) + fragment'] = ['user:pass@cdn#foobar', FALSE];
    $data['userinfo + host (domain) + path + query'] = ['user:pass@cdn/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (domain) + path + fragment'] = ['user:pass@cdn/foo/bar#foobar', FALSE];
    $data['userinfo + host (domain) + path + query + fragment'] = ['user:pass@cdn/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (domain) + query + fragment'] = ['user:pass@cdn/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (domain)'] = ['https://user:pass@cdn', FALSE];
    $data['scheme + userinfo + host (domain) + path'] = ['https://user:pass@cdn/foobar', FALSE];
    $data['scheme + userinfo + host (domain) + query'] = ['https://user:pass@cdn?foo=bar', FALSE];
    $data['scheme + userinfo + host (domain) + fragment'] = ['https://user:pass@cdn#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + path + query'] = ['https://user:pass@cdn/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (domain) + path + fragment'] = ['https://user:pass@cdn/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + path + query + fragment'] = ['https://user:pass@cdn/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + query + fragment'] = ['https://user:pass@cdn?foo=bar#foobar', FALSE];

    $data['host (domain) + port + path'] = ['cdn:1988/foo/bar', FALSE];
    $data['host (domain) + port + query'] = ['cdn:1988?foo=bar', FALSE];
    $data['host (domain) + port + fragment'] = ['cdn:1988#foobar', FALSE];
    $data['host (domain) + port + path + query'] = ['cdn:1988/foo/bar?foo=bar', FALSE];
    $data['host (domain) + port + path + fragment'] = ['cdn:1988/foo/bar#foobar', FALSE];
    $data['host (domain) + port + path + query + fragment'] = ['cdn:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['host (domain) + port + query + fragment'] = ['cdn:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + host (domain) + port + path'] = ['https://cdn:1988/foo/bar', FALSE];
    $data['scheme + host (domain) + port + query'] = ['https://cdn:1988?foo=bar', FALSE];
    $data['scheme + host (domain) + port + fragment'] = ['https://cdn:1988#foobar', FALSE];
    $data['scheme + host (domain) + port + path + query'] = ['https://cdn:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + host (domain) + port + path + fragment'] = ['https://cdn:1988/foo/bar#foobar', FALSE];
    $data['scheme + host (domain) + port + path + query + fragment'] = ['https://cdn:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (domain) + port + query + fragment'] = ['https://cdn:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['userinfo + host (domain) + port + path'] = ['user:pass@cdn:1988/foo/bar', FALSE];
    $data['userinfo + host (domain) + port + query'] = ['user:pass@cdn:1988?foo=bar', FALSE];
    $data['userinfo + host (domain) + port + fragment'] = ['user:pass@cdn:1988#foobar', FALSE];
    $data['userinfo + host (domain) + port + path + query'] = ['user:pass@cdn:1988/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (domain) + port + path + fragment'] = ['user:pass@cdn:1988/foo/bar#foobar', FALSE];
    $data['userinfo + host (domain) + port + path + query + fragment'] = ['user:pass@cdn:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (domain) + port + query + fragment'] = ['user:pass@cdn:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (domain) + port + path'] = ['https://user:pass@cdn:1988/foo/bar', FALSE];
    $data['scheme + userinfo + host (domain) + port + query'] = ['https://user:pass@cdn:1988?foo=bar', FALSE];
    $data['scheme + userinfo + host (domain) + port + fragment'] = ['https://user:pass@cdn:1988#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + port + path + query'] = ['https://user:pass@cdn:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (domain) + port + path + fragment'] = ['https://user:pass@cdn:1988/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + port + path + query + fragment'] = ['https://user:pass@cdn:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (domain) + port + query + fragment'] = ['https://user:pass@cdn:1988/foo/bar?foo=bar#foobar', FALSE];

    // Host = IPv4.
    $data['host (IPv4) '] = ['20.01.19.88', TRUE];
    $data['userinfo + host (IPv4)'] = ['user:pass@20.01.19.88', TRUE];
    $data['host (IPv4) + port'] = ['20.01.19.88:1988', TRUE];
    $data['userinfo + host (IPv4) + port'] = ['user:pass@20.01.19.88:1988', TRUE];

    $data['host (IPv4) + path'] = ['20.01.19.88/foo/bar', FALSE];
    $data['host (IPv4) + query'] = ['20.01.19.88?foo=bar', FALSE];
    $data['host (IPv4) + fragment'] = ['20.01.19.88#foobar', FALSE];
    $data['host (IPv4) + path + query'] = ['20.01.19.88/foo/bar?foo=bar', FALSE];
    $data['host (IPv4) + path + fragment'] = ['20.01.19.88/foo/bar#foobar', FALSE];
    $data['host (IPv4) + path + query + fragment'] = ['20.01.19.88/foo/bar?foo=bar#foobar', FALSE];
    $data['host (IPv4) + query + fragment'] = ['20.01.19.88/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme-relative + host (IPv4)'] = ['//20.01.19.88', FALSE];

    $data['scheme + host (IPv4)'] = ['https://20.01.19.88', FALSE];
    $data['scheme + host (IPv4) + path'] = ['https://20.01.19.88/foobar', FALSE];
    $data['scheme + host (IPv4) + query'] = ['https://20.01.19.88?foo=bar', FALSE];
    $data['scheme + host (IPv4) + fragment'] = ['https://20.01.19.88#foobar', FALSE];
    $data['scheme + host (IPv4) + path + query'] = ['https://20.01.19.88/foo/bar?foo=bar', FALSE];
    $data['scheme + host (IPv4) + path + fragment'] = ['https://20.01.19.88/foo/bar#foobar', FALSE];
    $data['scheme + host (IPv4) + path + query + fragment'] = ['https://20.01.19.88/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (IPv4) + query + fragment'] = ['https://20.01.19.88?foo=bar#foobar', FALSE];

    $data['userinfo + host (IPv4) + path'] = ['user:pass@20.01.19.88/foo/bar', FALSE];
    $data['userinfo + host (IPv4) + query'] = ['user:pass@20.01.19.88?foo=bar', FALSE];
    $data['userinfo + host (IPv4) + fragment'] = ['user:pass@20.01.19.88#foobar', FALSE];
    $data['userinfo + host (IPv4) + path + query'] = ['user:pass@20.01.19.88/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (IPv4) + path + fragment'] = ['user:pass@20.01.19.88/foo/bar#foobar', FALSE];
    $data['userinfo + host (IPv4) + path + query + fragment'] = ['user:pass@20.01.19.88/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (IPv4) + query + fragment'] = ['user:pass@20.01.19.88/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (IPv4)'] = ['https://user:pass@20.01.19.88', FALSE];
    $data['scheme + userinfo + host (IPv4) + path'] = ['https://user:pass@20.01.19.88/foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + query'] = ['https://user:pass@20.01.19.88?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv4) + fragment'] = ['https://user:pass@20.01.19.88#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + path + query'] = ['https://user:pass@20.01.19.88/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv4) + path + fragment'] = ['https://user:pass@20.01.19.88/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + path + query + fragment'] = ['https://user:pass@20.01.19.88/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + query + fragment'] = ['https://user:pass@20.01.19.88?foo=bar#foobar', FALSE];

    $data['host (IPv4) + port + path'] = ['20.01.19.88:1988/foo/bar', FALSE];
    $data['host (IPv4) + port + query'] = ['20.01.19.88:1988?foo=bar', FALSE];
    $data['host (IPv4) + port + fragment'] = ['20.01.19.88:1988#foobar', FALSE];
    $data['host (IPv4) + port + path + query'] = ['20.01.19.88:1988/foo/bar?foo=bar', FALSE];
    $data['host (IPv4) + port + path + fragment'] = ['20.01.19.88:1988/foo/bar#foobar', FALSE];
    $data['host (IPv4) + port + path + query + fragment'] = ['20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['host (IPv4) + port + query + fragment'] = ['20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + host (IPv4) + port + path'] = ['https://20.01.19.88:1988/foo/bar', FALSE];
    $data['scheme + host (IPv4) + port + query'] = ['https://20.01.19.88:1988?foo=bar', FALSE];
    $data['scheme + host (IPv4) + port + fragment'] = ['https://20.01.19.88:1988#foobar', FALSE];
    $data['scheme + host (IPv4) + port + path + query'] = ['https://20.01.19.88:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + host (IPv4) + port + path + fragment'] = ['https://20.01.19.88:1988/foo/bar#foobar', FALSE];
    $data['scheme + host (IPv4) + port + path + query + fragment'] = ['https://20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (IPv4) + port + query + fragment'] = ['https://20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['userinfo + host (IPv4) + port + path'] = ['user:pass@20.01.19.88:1988/foo/bar', FALSE];
    $data['userinfo + host (IPv4) + port + query'] = ['user:pass@20.01.19.88:1988?foo=bar', FALSE];
    $data['userinfo + host (IPv4) + port + fragment'] = ['user:pass@20.01.19.88:1988#foobar', FALSE];
    $data['userinfo + host (IPv4) + port + path + query'] = ['user:pass@20.01.19.88:1988/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (IPv4) + port + path + fragment'] = ['user:pass@20.01.19.88:1988/foo/bar#foobar', FALSE];
    $data['userinfo + host (IPv4) + port + path + query + fragment'] = ['user:pass@20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (IPv4) + port + query + fragment'] = ['user:pass@20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (IPv4) + port + path'] = ['https://user:pass@20.01.19.88:1988/foo/bar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + query'] = ['https://user:pass@20.01.19.88:1988?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + fragment'] = ['https://user:pass@20.01.19.88:1988#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + path + query'] = ['https://user:pass@20.01.19.88:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + path + fragment'] = ['https://user:pass@20.01.19.88:1988/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + path + query + fragment'] = ['https://user:pass@20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv4) + port + query + fragment'] = ['https://user:pass@20.01.19.88:1988/foo/bar?foo=bar#foobar', FALSE];

    // Host = IPv6.
    $data['host (IPv6) '] = ['2001:db8::ff00:42:8329', TRUE];
    $data['userinfo + host (IPv6)'] = ['user:pass@2001:db8::ff00:42:8329', TRUE];
    $data['host (IPv6) + port'] = ['2001:db8::ff00:42:8329:1988', TRUE];
    $data['userinfo + host (IPv6) + port'] = ['user:pass@2001:db8::ff00:42:8329:1988', TRUE];

    $data['scheme-relative + host (domain)'] = ['//2001:db8::ff00:42:8329', FALSE];

    $data['host (IPv6) + path'] = ['2001:db8::ff00:42:8329/foo/bar', FALSE];
    $data['host (IPv6) + query'] = ['2001:db8::ff00:42:8329?foo=bar', FALSE];
    $data['host (IPv6) + fragment'] = ['2001:db8::ff00:42:8329#foobar', FALSE];
    $data['host (IPv6) + path + query'] = ['2001:db8::ff00:42:8329/foo/bar?foo=bar', FALSE];
    $data['host (IPv6) + path + fragment'] = ['2001:db8::ff00:42:8329/foo/bar#foobar', FALSE];
    $data['host (IPv6) + path + query + fragment'] = ['2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];
    $data['host (IPv6) + query + fragment'] = ['2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + host (IPv6)'] = ['https://2001:db8::ff00:42:8329', FALSE];
    $data['scheme + host (IPv6) + path'] = ['https://2001:db8::ff00:42:8329/foobar', FALSE];
    $data['scheme + host (IPv6) + query'] = ['https://2001:db8::ff00:42:8329?foo=bar', FALSE];
    $data['scheme + host (IPv6) + fragment'] = ['https://2001:db8::ff00:42:8329#foobar', FALSE];
    $data['scheme + host (IPv6) + path + query'] = ['https://2001:db8::ff00:42:8329/foo/bar?foo=bar', FALSE];
    $data['scheme + host (IPv6) + path + fragment'] = ['https://2001:db8::ff00:42:8329/foo/bar#foobar', FALSE];
    $data['scheme + host (IPv6) + path + query + fragment'] = ['https://2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (IPv6) + query + fragment'] = ['https://2001:db8::ff00:42:8329?foo=bar#foobar', FALSE];

    $data['userinfo + host (IPv6) + path'] = ['user:pass@2001:db8::ff00:42:8329/foo/bar', FALSE];
    $data['userinfo + host (IPv6) + query'] = ['user:pass@2001:db8::ff00:42:8329?foo=bar', FALSE];
    $data['userinfo + host (IPv6) + fragment'] = ['user:pass@2001:db8::ff00:42:8329#foobar', FALSE];
    $data['userinfo + host (IPv6) + path + query'] = ['user:pass@2001:db8::ff00:42:8329/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (IPv6) + path + fragment'] = ['user:pass@2001:db8::ff00:42:8329/foo/bar#foobar', FALSE];
    $data['userinfo + host (IPv6) + path + query + fragment'] = ['user:pass@2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (IPv6) + query + fragment'] = ['user:pass@2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (IPv6)'] = ['https://user:pass@2001:db8::ff00:42:8329', FALSE];
    $data['scheme + userinfo + host (IPv6) + path'] = ['https://user:pass@2001:db8::ff00:42:8329/foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + query'] = ['https://user:pass@2001:db8::ff00:42:8329?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv6) + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + path + query'] = ['https://user:pass@2001:db8::ff00:42:8329/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv6) + path + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + path + query + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + query + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329?foo=bar#foobar', FALSE];

    $data['host (IPv6) + port + path'] = ['2001:db8::ff00:42:8329:1988/foo/bar', FALSE];
    $data['host (IPv6) + port + query'] = ['2001:db8::ff00:42:8329:1988?foo=bar', FALSE];
    $data['host (IPv6) + port + fragment'] = ['2001:db8::ff00:42:8329:1988#foobar', FALSE];
    $data['host (IPv6) + port + path + query'] = ['2001:db8::ff00:42:8329:1988/foo/bar?foo=bar', FALSE];
    $data['host (IPv6) + port + path + fragment'] = ['2001:db8::ff00:42:8329:1988/foo/bar#foobar', FALSE];
    $data['host (IPv6) + port + path + query + fragment'] = ['2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['host (IPv6) + port + query + fragment'] = ['2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + host (IPv6) + port + path'] = ['https://2001:db8::ff00:42:8329:1988/foo/bar', FALSE];
    $data['scheme + host (IPv6) + port + query'] = ['https://2001:db8::ff00:42:8329:1988?foo=bar', FALSE];
    $data['scheme + host (IPv6) + port + fragment'] = ['https://2001:db8::ff00:42:8329:1988#foobar', FALSE];
    $data['scheme + host (IPv6) + port + path + query'] = ['https://2001:db8::ff00:42:8329:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + host (IPv6) + port + path + fragment'] = ['https://2001:db8::ff00:42:8329:1988/foo/bar#foobar', FALSE];
    $data['scheme + host (IPv6) + port + path + query + fragment'] = ['https://2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + host (IPv6) + port + query + fragment'] = ['https://2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['userinfo + host (IPv6) + port + path'] = ['user:pass@2001:db8::ff00:42:8329:1988/foo/bar', FALSE];
    $data['userinfo + host (IPv6) + port + query'] = ['user:pass@2001:db8::ff00:42:8329:1988?foo=bar', FALSE];
    $data['userinfo + host (IPv6) + port + fragment'] = ['user:pass@2001:db8::ff00:42:8329:1988#foobar', FALSE];
    $data['userinfo + host (IPv6) + port + path + query'] = ['user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar', FALSE];
    $data['userinfo + host (IPv6) + port + path + fragment'] = ['user:pass@2001:db8::ff00:42:8329:1988/foo/bar#foobar', FALSE];
    $data['userinfo + host (IPv6) + port + path + query + fragment'] = ['user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['userinfo + host (IPv6) + port + query + fragment'] = ['user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];

    $data['scheme + userinfo + host (IPv6) + port + path'] = ['https://user:pass@2001:db8::ff00:42:8329:1988/foo/bar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + query'] = ['https://user:pass@2001:db8::ff00:42:8329:1988?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329:1988#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + path + query'] = ['https://user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + path + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329:1988/foo/bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + path + query + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];
    $data['scheme + userinfo + host (IPv6) + port + query + fragment'] = ['https://user:pass@2001:db8::ff00:42:8329:1988/foo/bar?foo=bar#foobar', FALSE];

    // phpcs:enable

    return $data;
  }

}
