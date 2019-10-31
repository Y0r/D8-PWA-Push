<?php

namespace Drupal\pwa_push\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Creates a new NodeType instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   */
  public function __construct(EntityStorageInterface $entity_storage, EntityFieldManager $entity_field_manager) {
    $this->entityStorage = $entity_storage;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('node_type'),
      $container->get('entity_field.manager')
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
  
    $form['messages_settings']['send_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send image in message'),
      '#default_value' => NULL !== $config->get('send_image') ? $config->get('send_image') : false,
      '#description' => $this->t('Image will be send to subscribers.'),
    ];
    
    $form['messages_settings']['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose field:'),
      '#options' => $this->getImageFields($config->get('enabled_content_types')),
      '#default_value' => $config->get('image_field') ? $config->get('image_field') : 0,
      '#states' => [
        'invisible' => [
          ':input[name="send_image"]' => ['checked' => FALSE],
        ],
      ],
    ];
    
    return $form;
  }
  
  function getImageFields($bundles) {
    $entity_type_id = 'node';
    $listFields = [
      0 => "None",
    ];
    
    foreach ($bundles as $bundle) {
      if (!empty($bundle)) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
        foreach ($fields as $field_name => $field_definition) {
          if (!empty($field_definition->getTargetBundle())) {
            if ($field_definition->getType() == "image"){
              $listFields[$field_name] = "$bundle - $field_name";
            }
          }
        }
      }
    }
    
    return $listFields;
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
      ->set('send_image', $form_state->getValue('send_image'))
      ->set('image_field', $form_state->getValue('image_field'))
      ->save();
  }

}
