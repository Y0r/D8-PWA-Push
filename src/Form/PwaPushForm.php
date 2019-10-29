<?php

namespace Drupal\pwa_push\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Minishlink\WebPush\VAPID;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdvancedpwaForm.
 */
class PwaPushForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file name.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pwa_push.pwa_push',
      'pwa_push.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pwa_push_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pwa_push.pwa_push');
    $pwa_push_config = $this->config('pwa_push.settings');
    $form = parent::buildForm($form, $form_state);

    $form['push_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Accept push notification'),
      '#open' => FALSE,
    ];

    $form['push_settings']['status_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable push notifications'),
      '#default_value' => NULL !== $pwa_push_config->get('status.all') ? $pwa_push_config->get('status.all') : TRUE,
      '#description' => $this->t('Disabling the push notifications will ensure that no user will be able to receive push notifications'),
    ];

    $form['gcm_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GCM Key'),
      '#description' => $this->t('Google Cloud Messaging (GCM) key'),
      '#maxlength' => 50,
      '#size' => 50,
      '#default_value' => $config->get('gcm_key'),
    ];

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#description' => $this->t('VAPID authentication public key.'),
      '#maxlength' => 100,
      '#size' => 100,
      '#default_value' => $config->get('public_key'),
      '#required' => TRUE,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#description' => $this->t('VAPID authentication private key.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('private_key'),
      '#required' => TRUE,
    ];

    $form['icon'] = [
      '#type' => 'details',
      '#title' => $this->t('advanced_pwa notification icon'),
      '#open' => TRUE,
    ];

    $form['icon']['settings'] = [
      '#type' => 'container',
    ];

    $form['icon']['settings']['icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon image'),
      '#default_value' => $config->get('icon_path'),
      '#disabled' => 'disabled',
      '#description' => $this->t("generate the public key to upload image"),
    ];

    $form['icon']['settings']['icon_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload icon image'),
      '#maxlength' => 40,
      '#description' => $this->t("Upload advanced_pwa notification icon. Maximum allowed image dimensions is 144 x 144. If image having larger dimensions is submitted then it will be resized to 144 * 144"),
      '#upload_location' => file_default_scheme() . '://images/pwaimages/',
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_extensions' => ['png gif jpg jpeg'],
      ],
      '#states' => [
        'disabled' => [
          ':input[name="public_key"]' => ['filled' => FALSE],
        ],
      ],
    ];

    $public_key = $config->get('public_key');
    if (empty($public_key)) {
      $form['actions']['generate'] = [
        '#type' => 'submit',
        '#value' => $this->t('Generate keys'),
        '#limit_validation_errors' => [],
        '#submit' => ['::generateKeys'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('file')) {
      // Check for a new uploaded logo.
      if (isset($form['icon'])) {
        $file = file_save_upload('icon_upload');
        if ($file) {
          $error = file_validate_image_resolution($file[0], 144, 144);
          if ($error) {
            $form_state->setErrorByName('icon_upload', $this->t('Image diamention is greater than 144 x 144.'));
          }
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('icon_upload', $file);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (!empty(file_save_upload('icon_upload'))) {
      $file = file_save_upload('icon_upload');
      $filename = $this->fileSystem->copy($file[0]->getFileUri(), 'public://images/pwaimages/');
      $form_state->setValue('icon_path', $filename);
    }

    $this->config('pwa_push.pwa_push')
      ->set('gcm_key', trim($form_state->getValue('gcm_key')))
      ->set('public_key', trim($form_state->getValue('public_key')))
      ->set('private_key', trim($form_state->getValue('private_key')))
      ->set('icon_path', $form_state->getValue('icon_path'))
      ->save();

    $this->config('pwa_push.settings')
      ->set('status.all', $form_state->getValue('status_all'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function generateKeys(array &$form, FormStateInterface $form_state) {
    $keys = VAPID::createVapidKeys();
    $this->config('pwa_push.pwa_push')
      ->set('public_key', $keys['publicKey'])
      ->set('private_key', $keys['privateKey'])
      ->save();
  }

}
