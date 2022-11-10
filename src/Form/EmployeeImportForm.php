<?php

namespace Drupal\bkh_employee\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Employees Form.
 */
class EmployeeImportForm extends FormBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EmployeeForm object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FileSystemInterface $file_system, PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystem = $file_system;
    $this->store = $temp_store_factory->get('bkh_employee');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'employee_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting csvs list if exists already.
    $csvs = $this->store->get('csvs');

    $form['csv_list'] = [
      '#theme' => 'item_list',
      '#items' => $csvs ? array_map(fn($item) => ['#markup' => $item], $csvs) : [['#markup' => $this->t('No file chosen')]],
      '#attributes' => [
        'class' => [
          'csv-list',
        ],
      ],
    ];

    // Add more, save actions.
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '1',
    ];
    $form['actions']['choose_csv_file'] = [
      '#type' => 'link',
      '#title' => $this->t('Choose CSV file'),
      '#url' => Url::fromRoute('bkh_employee.csv_list_form'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
        'data-dialog-type' => 'modal',
      ],
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#ajax' => [
        'callback' => [$this, 'submitAjax'],
      ],
    ];

    // CSV imported modal.
    $form['csv_imported'] = [
      '#type' => 'link',
      '#title' => $this->t('CSV imported'),
      '#url' => Url::fromRoute('bkh_employee.employee_import_controller_csv_imported'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'hidden',
          'csv-imported',
        ],
        'data-dialog-type' => 'modal',
      ],
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];

    // CSV empty import.
    $form['csv_empty_import'] = [
      '#type' => 'link',
      '#title' => $this->t('CSV empty import'),
      '#url' => Url::fromRoute('bkh_employee.employee_import_controller_csv_empty_import'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'hidden',
          'csv-empty-import',
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';

    // If there are no csv files.
    if (!$this->fileSystem->prepareDirectory($dest_dir)) {
      $form_state->setErrorByName('csv_list', $this->t('There are no CSVs to import'));
      return;
    }

    // Getting chosen filenames.
    $csvs = $this->store->get('csvs');

    // Getting files that are saved in tmp folder.
    $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';
    $filenames = array_map(fn($item) => $item->filename, $this->fileSystem->scanDirectory($dest_dir, '/^\d+\.csv$/'));

    // Checking if files exist.
    foreach ($csvs as $csv) {

      if (array_search($csv, $filenames) === FALSE) {
        $form_state->setErrorByName('csv_list', $this->t('Empoyees list is not up to date. Please regenerate employee data'));
        break;
      }
    }
  }

  /**
   * An AJAX callback for form submit.
   */
  public function submitAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Empties CSV html list.
    $csv_list = [
      '#theme' => 'item_list',
      '#items' => [['#markup' => $this->t('No file chosen')]],
      '#attributes' => [
        'class' => [
          'csv-list',
        ],
      ],
    ];
    $response->addCommand(new ReplaceCommand('.csv-list', $csv_list));

    // Getting chosen filenames.
    $csvs = $this->store->get('csvs');
    if ($csvs) {
      // Displays popup on success.
      $response->addCommand(new InvokeCommand('.csv-imported', 'click'));
    }
    else {
      // Displays popup on empty import.
      $response->addCommand(new InvokeCommand('.csv-empty-import', 'click'));
    }

    $this->cleanUpImport();

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo batch?
    $this->runImport();
  }

  /**
   * Runs import.
   */
  private function runImport() {
    $storage = $this->entityTypeManager->getStorage('employee');
    $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';

    // Reading data from csvs and creating entities.
    $csvs = $this->store->get('csvs');
    foreach ($csvs as $csv) {
      $fp = fopen($dest_dir . '/' . $csv, 'r');

      while ($employee = fgetcsv($fp)) {
        $storage->create([
          'name' => $employee[0],
          'email' => $employee[1],
        ])->save();
      }

      fclose($fp);
    }
  }

  /**
   * Cleans up csvs data.
   */
  private function cleanUpImport() {
    // Reseting csvs for import.
    $this->store->set('csvs', []);

    // Deleting csvs.
    // $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';
    // $this->fileSystem->deleteRecursive($dest_dir);
  }

}
