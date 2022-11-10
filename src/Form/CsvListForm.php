<?php

namespace Drupal\bkh_employee\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Employees Form.
 */
class CsvListForm extends FormBase {

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
   * Constructs a new CsvListForm object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(FileSystemInterface $file_system, PrivateTempStoreFactory $temp_store_factory) {
    $this->fileSystem = $file_system;
    $this->store = $temp_store_factory->get('bkh_employee');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csv_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $dest_dir = $this->fileSystem->getTempDirectory() . '/employees';

    // Checking if CSVs directory is not empty.
    if (!$this->fileSystem->prepareDirectory($dest_dir)) {
      $form['empty_list'] = [
        '#markup' => $this->t('There are no CSVs to import'),
      ];

      return $form;
    }

    // Getting all saved CSVs to display in form.
    $csvs = $this->fileSystem->scanDirectory($dest_dir, '/^\d+\.csv$/');
    $filenames = array_map(fn($item) => $item->filename, $csvs);

    $form['csvs'] = [
      '#title' => $this->t('Choose files'),
      '#type' => 'checkboxes',
      '#options' => array_combine($filenames, $filenames),
      '#default_value' => $this->store->get('csvs'),
      '#ajax' => [
        'callback' => [$this, 'csvsAjax'],
      ],
    ];

    return $form;
  }

  /**
   * An AJAX callback for choosing csvs.
   */
  public function csvsAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $csvs = array_filter($form_state->getValue('csvs'), fn($item) => $item);

    // Saving csvs for import.
    $this->store->set('csvs', array_keys($csvs));

    // Displaying the list of csvs for import.
    $csv_list = [
      '#theme' => 'item_list',
      '#items' => $csvs ? array_map(fn($item) => ['#markup' => $item], $csvs) : [['#markup' => $this->t('No file chosen')]],
      '#attributes' => [
        'class' => [
          'csv-list',
        ],
      ],
    ];
    $response->addCommand(new ReplaceCommand('.csv-list', $csv_list));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
