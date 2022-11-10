<?php

namespace Drupal\bkh_employee\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class for EmployeeImportController.
 */
class EmployeeImportController extends ControllerBase {

  /**
   * CSV Saved.
   *
   * @return string
   *   Return renderer array of csv saved message.
   */
  public function csvSaved() {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [$this->t('Data has been saved')],
      ],
    ];
  }

  /**
   * CSV Imported.
   *
   * @return string
   *   Return renderer array of csv imported message.
   */
  public function csvImported() {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [$this->t('Data has been imported')],
      ],
    ];
  }

  /**
   * CSV Empty Import.
   *
   * @return string
   *   Return renderer array of csv empty import message.
   */
  public function csvEmptyImport() {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [$this->t('No data to import')],
      ],
    ];
  }

}
