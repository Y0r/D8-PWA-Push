<?php

namespace Drupal\pwa_push\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Class AdvancedpwaSubscriptionForm.
 */
class PwaPushSubscriptionForm extends ConfigFormBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new NodeType instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pwa_push.pwa_push.subscription',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pwa_push_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pwa_push.pwa_push.subscription');
    $form = parent::buildForm($form, $form_state);

    $contentTypes = $this->entityStorage->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['activate_feature'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('activate_feature'),
      '#title' => $this->t('Activate published content notifications'),
      '#description' => $this->t('Notifications will be pushed for content of following checked content types is published'),
    ];
    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Avaliable Content types'),
      '#options' => $contentTypesList,
      '#default_value' => $config->get('enabled_content_types'),
      '#states' => [
        'enabled' => [
          ':input[name="activate_feature"]' => ['checked' => TRUE],
        ],
      ],
    ];
    
    $form['messages_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Other messages settings'),
      '#open' => FALSE,
    ];
    
    $form['messages_settings']['insert_node_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send node title and summary in message'),
      '#default_value' => NULL !== $config->get('insert_node_data') ? $config->get('insert_node_data') : FALSE,
      '#description' => $this->t('By message have information about new content creating. Choose this option if you want send title and summary from node.'),
    ];
    
    $form['messages_settings']['url_to_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect user to content after click on message'),
      '#default_value' => NULL !== $config->get('url_to_content') ? $config->get('url_to_content') : false,
      '#description' => $this->t('User will redirect to created node.'),
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable('pwa_push.pwa_push.subscription')
      ->set('enabled_content_types', $form_state->getValue('enabled_content_types'))
      ->set('activate_feature', $form_state->getValue('activate_feature'))
      ->set('insert_node_data', $form_state->getValue('insert_node_data'))
      ->set('url_to_content', $form_state->getValue('url_to_content'))
      ->save();
  }

}
