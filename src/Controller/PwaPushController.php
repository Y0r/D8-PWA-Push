<?php

namespace Drupal\pwa_push\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pwa_push\Model\SubscriptionsDatastorage;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class pwaPushController.
 */
class PwaPushController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')->get('pwa_push'),
      $container->get('entity_type.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, LoggerInterface $logger, EntityTypeManagerInterface $entity_type, StateInterface $state) {
    $this->database = $database;
    $this->logger = $logger;
    $this->fileStorage = $entity_type->getStorage('file');
    $this->state = $state;
  }

  /**
   * Subscribe.
   *
   * @return string
   *   Return Hello string.
   */
  public function subscribe(Request $request) {
    if ($request) {
      $message = 'Subscribe: ' . $request->getContent();
      $this->logger->info($message);

      $data = json_decode($request->getContent(), TRUE);
      $entry['subscription_endpoint'] = $data['endpoint'];
      $entry['subscription_data'] = serialize(['key' => $data['key'], 'token' => $data['token']]);
      $entry['registered_on'] = strtotime(date('Y-m-d H:i:s'));
      $success = SubscriptionsDatastorage::insert($entry);
      return new JsonResponse([$success]);
    }
    return NULL;
  }

  /**
   * Un-subscribe.
   *
   * @return string
   *   Return Hello string.
   */
  public function unsubscribe(Request $request) {
    if ($request) {
      $message = 'Un-subscribe : ' . $request->getContent();
      $this->logger->info($message);

      $data = json_decode($request->getContent(), TRUE);
      $entry['subscription_endpoint'] = $data['endpoint'];
      $success = SubscriptionsDatastorage::delete($entry);
      return new JsonResponse([$success]);
    }
  }

  /**
   * List of all subscribed users.
   */
  public function subscriptionList() {
    // The table description.
    $header = [
      ['data' => $this->t('Id')],
      ['data' => $this->t('Subscription Endpoint')],
      ['data' => $this->t('Registeration Date')],
    ];
    $getFields = [
      'id',
      'subscription_endpoint',
      'registered_on',
    ];
    $query = $this->database->select(SubscriptionsDatastorage::$subscriptionTable);
    $query->fields(SubscriptionsDatastorage::$subscriptionTable, $getFields);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $result = $pager->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        'data' => [
          'id' => $row->id,
          'register_id' => $row->subscription_endpoint,
          'date' => date('d/m/Y', $row->registered_on),
        ],
      ];
    }
    if (empty($rows)) {
      $markup = $this->t('No record found.');
    }
    else {
      $markup = $this->t('List of All Subscribed Users.');
    }
    $build = [
      '#markup' => $markup,
    ];
    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

}
