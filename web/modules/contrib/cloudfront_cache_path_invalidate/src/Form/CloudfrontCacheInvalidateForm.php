<?php

namespace Drupal\cloudfront_cache_path_invalidate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;

/**
 * Configure Cloudfront Cache Invalidate for this site.
 */
class CloudfrontCacheInvalidateForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new Cloudfront Cache clear form object.
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerInterface $logger
  ) {
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory')->get('cloudfront_cache_path_invalidate')
    );
  }

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'cci_Cloudfront_cache.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cci_Cloudfront_cache_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::SETTINGS);

    $cloudfront_url_array = [
      '#type' => 'textarea',
      '#title' => $this->t('Cloudfront URL to invalidate'),
      '#description' => $this->t('Specify the existing path you wish to invalidate. For example: /sector/*, /state/*. Enter one value per line'),
      '#placeholder' => 'Cloudfront URL',
    ];

    if ($config->get('cloudfront_url')) {
      $cloudfront_url_array['#default_value'] = $config->get('cloudfront_url');
    }

    $form['cloudfront_url'] = $cloudfront_url_array;

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invalidate URL'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get the URL.
    $url_value = explode("\n", $form_state->getValue('cloudfront_url'));

    if (!empty($url_value) && is_array($url_value) && count($url_value) > 0) {
      foreach ($url_value as $value) {
        if (substr($value, 0, 1) != '/' && !empty($value)) {
          $form_state->setErrorByName('url', $this->t('The Cloudfront URL introduced is not valid.'));
        }
      }
    }

    else {
      $form_state->setErrorByName('url', $this->t('The Cloudfront URL introduced is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $url_value = explode("\n", $form_state->getValue('cloudfront_url'));
      // Get the Paths.
      $paths = [];
      foreach ($url_value as $value) {
        if ($value) {
          $paths[] = trim($value);
        }
      }

      // Invalidate URL.
      list($status, $message) = cloudfront_cache_path_invalidate_url($paths);

      if ($status === TRUE) {
        $this->messenger->addMessage('Cloudfront Cache invalidation is in progress.', $this->messenger::TYPE_STATUS);
      }
      else {
        $this->messenger->addMessage($message, $this->messenger::TYPE_ERROR);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addMessage($e->getMessage(), $this->messenger::TYPE_ERROR);
    }

  }

}
