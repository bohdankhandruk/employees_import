<?php

namespace Drupal\bkh_employee\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Employee entity.
 *
 * @ingroup bkh_employee
 *
 * @ContentEntityType(
 *   id = "employee",
 *   label = @Translation("Employee"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bkh_employee\EmployeeListBuilder",
 *     "views_data" = "Drupal\bkh_employee\Entity\EmployeeViewsData",
 *
 *     "access" = "Drupal\bkh_employee\EmployeeAccessControlHandler",
 *   },
 *   base_table = "employee",
 *   translatable = FALSE,
 *   admin_permission = "administer employee entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name",
 *     "email" = "email",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Employee extends ContentEntityBase implements EmployeeInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Employee entity.'));
    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of the Employee entity.'));

    return $fields;
  }

}
