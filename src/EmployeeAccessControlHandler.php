<?php

namespace Drupal\bkh_employee;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Employee entity.
 *
 * @see \Drupal\bkh_employee\Entity\Employee.
 */
class EmployeeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bkh_employee\Entity\EmployeeInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished employee entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published employee entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit employee entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete employee entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add employee entities');
  }

}
