<?php

require_once 'userpayment.civix.php';
use CRM_Userpayment_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function userpayment_civicrm_config(&$config) {
  _userpayment_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function userpayment_civicrm_xmlMenu(&$files) {
  _userpayment_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function userpayment_civicrm_install() {
  _userpayment_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function userpayment_civicrm_postInstall() {
  CRM_Core_Payment_MJWTrait::createPaymentInstrument(['name' => 'Bulk Payment']);

  // Create and set "Bulk Payment" financial type as default
  $financialTypeParams = [
    'name' => "Bulk Payment",
  ];
  try {
    $financialType = civicrm_api3('FinancialType', 'getsingle', $financialTypeParams);
    $financialTypeParams['id'] = $financialType['id'];
  }
  catch (Exception $e) {
    // Do nothing
  }

  $financialTypeParams['description'] = "For payments made via the bulk contributions method";
  $financialTypeParams['is_active'] = 1;
  $financialTypeParams['is_reserved'] = 1;
  $financialType = civicrm_api3('FinancialType', 'create', $financialTypeParams);
  \Civi::settings()->set('userpayment_bulkfinancialtype', $financialType['id']);

  _userpayment_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function userpayment_civicrm_uninstall() {
  _userpayment_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function userpayment_civicrm_enable() {
  _userpayment_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function userpayment_civicrm_disable() {
  _userpayment_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function userpayment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _userpayment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function userpayment_civicrm_managed(&$entities) {
  _userpayment_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function userpayment_civicrm_caseTypes(&$caseTypes) {
  _userpayment_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function userpayment_civicrm_angularModules(&$angularModules) {
  _userpayment_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function userpayment_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _userpayment_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function userpayment_civicrm_entityTypes(&$entityTypes) {
  _userpayment_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Intercept form functions
 */
function userpayment_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Admin_Form_Generic':
      $min = ((boolean) \Civi::settings()->get('debug_enabled')) ? '' : '.min';
      $method = new ReflectionMethod('CRM_Admin_Form_Generic', 'getSettingPageFilter');
      $method->setAccessible(true);
      $filter = $method->invoke($form);
      switch ($filter) {
        case 'userpayment_paymentadd':
          CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, "js/settings_addpayment{$min}.js");
          break;

        case 'userpayment_paymentbulk':
          CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, "js/settings_bulkpayment{$min}.js");
          break;
      }
      break;
  }
}

/**
 * Add navigation for GiftAid under "Administer/CiviContribute" menu
 */
function userpayment_civicrm_navigationMenu(&$menu) {
  $item =  [
    'label' => E::ts('User Payment Forms'),
    'name'       => 'admin_userpayment',
    'url'        => NULL,
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', $item);
  $item =  [
    'label' => E::ts('General Settings'),
    'name'       => 'admin_userpayment_general',
    'url'        => 'civicrm/admin/setting/userpayment_general?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/admin_userpayment', $item);
  $item =  [
    'label' => E::ts('Make Payment'),
    'name'       => 'admin_userpayment_paymentadd',
    'url'        => 'civicrm/admin/setting/userpayment_paymentadd?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/admin_userpayment', $item);
  $item =  [
    'label' => E::ts('Bulk Payment'),
    'name'       => 'admin_userpayment_paymentbulk',
    'url'        => 'civicrm/admin/setting/userpayment_paymentbulk?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/admin_userpayment', $item);
  $item =  [
    'label' => E::ts('Bulk Payment Invoice'),
    'name'       => 'admin_userpayment_paymentbulkinvoice',
    'url'        => 'civicrm/admin/setting/userpayment_paymentbulkinvoice?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/admin_userpayment', $item);
  $item =  [
    'label' => E::ts('Collect Payments'),
    'name'       => 'admin_userpayment_paymentbulkinvoice',
    'url'        => 'civicrm/admin/setting/userpayment_paymentcollect?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];
  _userpayment_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/admin_userpayment', $item);
  _userpayment_civix_navigationMenu($menu);
}

function userpayment_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName !== 'Contribution') {
    return;
  }

  switch ($op) {
    case 'delete':
        CRM_Userpayment_BulkContributions::removeFromDeletedBulkContribution($id);
      break;

  }
}

function userpayment_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName !== 'Contribution') {
    return;
  }

  switch ($op) {
    case 'edit':
      if (CRM_Core_Transaction::isActive()) {
        CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, 'CRM_Userpayment_BulkContributions::removeFromBulkContribution', [$objectId]);
      }
      else {
        CRM_Userpayment_BulkContributions::removeFromBulkContribution($objectId);
      }
      break;
  }
}
