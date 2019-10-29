<?php

namespace Drupal\pwa_push\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pwa_push\Model\SubscriptionsDatastorage;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class AdvancedpwaBroadcastForm.
 */
class PwaPushBroadcastForm extends FormBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * QueueFactory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, QueueFactory $queue) {
    $this->database = $database;
    $this->queueFactory = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pwa_push_broadcast_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['help'] = [
      '#markup' => $this->t('Message will be sent to all subscribed users when the cron will be executed next time.'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Message'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to broadcast'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Notification'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $advanced_pwa_config = $this->config('pwa_push.pwa_push');
    $icon = $advanced_pwa_config->get('icon_path');
    $icon_path = file_create_url($icon);

    $entry = [
      'title' => $form_state->getValue('title'),
      'message' => $form_state->getValue('message'),
      'icon' => $icon_path,
      'url' => "",
      'content-details' => [
        'nodeid' => "",
        'nodetype' => "",
      ],
    ];
    $notification_data = Json::encode($entry);
    $subscriptions = SubscriptionsDatastorage::loadAll();

    $pwa_push_public_key = $advanced_pwa_config->get('public_key');
    $pwa_push_private_key = $advanced_pwa_config->get('private_key');

    if (empty($pwa_push_public_key) && empty($pwa_push_private_key)) {
      $this->messenger()->addError($this->t('Please set public & private key.'), 'error');
    }
    if (!empty($subscriptions) && !empty($pwa_push_public_key) && !empty($pwa_push_private_key)) {
      $queue = $this->queueFactory->get('cron_send_notification');
      $item = new \stdClass();
      $item->subscriptions = $subscriptions;
      $item->notification_data = $notification_data;
      $queue->createItem($item);
      $this->messenger()->addMessage($this->t('message is added to queue successfully'));
    }
  }

}
