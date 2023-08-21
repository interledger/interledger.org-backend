<?php

declare(strict_types = 1);

// @codingStandardsIgnoreFile

namespace Drupal\cdn_ui\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo Lift the config validation logic out of this module into core.
 */
abstract class ValidatableConfigFormBase extends ConfigFormBase {

  /***
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager) {
    $this->typedConfigManager = $typed_config_manager;
    parent::__construct($config_factory);
  }

  abstract protected static function getMainConfigName() : string;

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = static::mapFormValuesToConfig($form_state, $this->config(static::getMainConfigName()));
    $typed_config = $this->typedConfigManager->createFromNameAndData(static::getMainConfigName(), $config->getRawData());

    $violations = $typed_config->validate();
    foreach ($violations as $violation) {
      $form_state->setErrorByName(static::mapViolationPropertyPathsToFormNames($violation->getPropertyPath()), $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = static::mapFormValuesToConfig($form_state, $this->config(static::getMainConfigName()));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  abstract protected static function mapFormValuesToConfig(FormStateInterface $form_state, Config $config);

  protected static function mapViolationPropertyPathsToFormNames(string $property_path) : string {
    return str_replace('.', '][', $property_path);
  }

}
