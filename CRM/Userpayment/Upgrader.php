<?php
use CRM_Userpayment_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Userpayment_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  public function postInstall() {
    $this->addCustomFields();
  }

  /**
   * Add the custom fields required by this extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function addCustomFields() {
    $group = civicrm_api3('CustomGroup', 'get', ['name' => 'bulk_payments']);
    if (empty($group['id'])) {
      $group = civicrm_api3('CustomGroup', 'create', [
        'title' => "Bulk Payments",
        'extends' => "Contribution",
        'name' => "bulk_payments",
      ]);
    }

    $field = civicrm_api3('CustomField', 'get', ['name' => 'identifier']);
    if (empty($field['id'])) {
      $field = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "bulk_payments",
        'name' => "identifier",
        'label' => "Identifier",
        'data_type' => "String",
        'html_type' => "Text",
        'is_searchable' => 1,
      ]);
    }
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1000() {
    $this->ctx->log->info('Adding custom fields');
    $this->addCustomFields();
    return TRUE;
  }

}
