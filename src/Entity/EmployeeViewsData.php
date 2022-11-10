<?php

namespace Drupal\bkh_employee\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Employee entities.
 */
class EmployeeViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
