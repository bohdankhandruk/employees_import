<?php

namespace Drupal\bkh_employee\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Employees Form.
 */
class EmployeeForm extends FormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $store;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EmployeeForm object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(FileSystemInterface $file_system, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger) {
    $this->fileSystem = $file_system;
    $this->store = $temp_store_factory->get('bkh_employee');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'employee_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['validation_errors_container'] = [
      '#type' => 'container',
      'validation_errors' => [
        '#theme' => 'status_messages',
        '#message_list' => [],
      ],
      '#attributes' => [
        'class' => [
          'validation-errors',
        ],
      ],
    ];

    $form['employees'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['employees'],
      ],
      '#weight' => '1',
    ];

    $employees = $form_state->getValue('employees') ?? [];

    // Loop through existing epmloyees.
    foreach (array_values($employees) as $key => $employee) {
      $form['employees'][$key + 1] = [
        '#type' => 'fieldset',
        '#weight' => '1',
      ];
      $form['employees'][$key + 1]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $employee['name'],
        '#required' => TRUE,
        '#weight' => '0',
      ];
      $form['employees'][$key + 1]['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#default_value' => $employee['email'],
        '#required' => TRUE,
        '#weight' => '0',
      ];
    }

    // Default employee fieldset.
    $form['employees'][0] = [
      '#type' => 'fieldset',
      '#weight' => '1',
    ];
    $form['employees'][0]['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => '',
      '#required' => TRUE,
      '#weight' => '0',
    ];
    $form['employees'][0]['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => '',
      '#required' => TRUE,
      '#weight' => '0',
    ];

    // Add more, save actions.
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '1',
    ];
    $form['actions']['add_more'] = [
      '#type' => 'submit',
      '#name' => 'add_more',
      '#value' => $this->t('Add More'),
      '#submit' => [[$this, 'addMore']],
      '#ajax' => [
        'callback' => [$this, 'addMoreAjax'],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'submitAjax'],
      ],
    ];

    // CSV saved modal.
    $form['csv_saved'] = [
      '#type' => 'link',
      '#title' => $this->t('CSV saved'),
      '#url' => Url::fromRoute('bkh_employee.employee_import_controller_csv_saved'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'hidden',
          'csv-saved',
        ],
        'data-dialog-type' => 'modal',
      ],
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];

    return $form;
  }

  /**
   * An AJAX callback for add more button.
   */
  public function addMoreAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $employees = array_filter($form['employees'], fn($item, $key) => is_int($key), ARRAY_FILTER_USE_BOTH);
    $employees[0]['name']['#value'] = $employees[0]['email']['#value'] = '';

    $response->addCommand(new AppendCommand('.employees', $employees[0]));

    return $response;
  }

  /**
   * Submit callback for add more button.
   */
  public function addMore(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * An AJAX callback for form submit.
   */
  public function submitAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Display either errors or popup with message about success.
    if ($errors = $form_state->getErrors()) {
      foreach ($errors as $error) {
        $form['validation_errors_container']['validation_errors']['#message_list']['error'][] = $error;
      }

      $response->addCommand(new ReplaceCommand('.validation-errors', $form['validation_errors_container']));

      // @todo do not clear all messages.
      $this->messenger->deleteAll();
    }
    else {
      $form['validation_errors_container']['validation_errors']['#message_list'] = [];

      $response->addCommand(new ReplaceCommand('.validation-errors', $form['validation_errors_container']));
      $response->addCommand(new InvokeCommand('.csv-saved', 'click'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Reseting csvs for import.
    $this->store->set('csvs', []);

    // Preparing directory.
    $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';

    if (!$this->fileSystem->prepareDirectory($dest_dir)) {
      $this->fileSystem->mkdir($dest_dir);
    }

    // Writing data.
    $fp = fopen($dest_dir . '/' . time() . '.csv', 'w');

    $employees = $form_state->getValue('employees');
    foreach ($employees as $employee) {
      fputcsv($fp, $employee);
    }

    fclose($fp);
  }

}
