<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * The class that handles all the AJAX callbacks for CRM_Userpayment
 * Class CRM_Userpayment_AJAX
 */
class CRM_Userpayment_AJAX {

  /**
   * Get a datatable formatted list of all bulk contributions for a specific identifier
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getBulkContributions() {
    $requiredParameters = [
      'cnum' => 'String',
    ];
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams($requiredParameters);

    $entities = CRM_Userpayment_BulkContributions::getBulkContributions($params);

    $datatableDT = [
      'recordsFiltered' => count($entities),
      'recordsTotal' => count($entities),
    ];
    $datatableDT['data'] = $entities;

    CRM_Utils_JSON::output($datatableDT);
  }

  /**
   * Remove a bulk contribution from a collection of contributions
   * @throws \CiviCRM_API3_Exception
   */
  public static function removeBulkContribution() {
    $requiredParameters = [
      'coid' => 'Positive',
    ];
    $params = CRM_Core_Page_AJAX::validateParams($requiredParameters);

    $contribution = civicrm_api3('Contribution', 'setvalue', [
      'field' => 'check_number',
      'id' => $params['coid'],
      'value' => NULL,
    ]);
  }

  /**
   * Add a bulk contribution to a collection of contributions
   * @throws \CiviCRM_API3_Exception
   */
  public static function addBulkContribution() {
    $requiredParameters = [
      'coid' => 'Positive',
      'cnum' => 'String',
    ];
    $params = CRM_Core_Page_AJAX::validateParams($requiredParameters);

    $existingContribution = civicrm_api3('Contribution', 'getsingle', [
      'return' => ['check_number', 'contribution_status_id'],
      'id' => $params['coid'],
    ]);

    // Don't add a second time
    if ($existingContribution['check_number'] === $params['cnum']) {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode(['message' => "You have already added {$params['coid']} to this bulk payment!"]);
      CRM_Utils_System::civiExit(1);
    }
    // Only allow adding pending contributions
    if ((int)$existingContribution['contribution_status_id'] !== (int)CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode(['message' => "Cannot add {$params['coid']} because it is not pending payment"]);
      CRM_Utils_System::civiExit(1);
    }
    // Don't add if it's already added to another bulk contribution
    if (!empty($existingContribution['check_number'])) {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode(['message' => 'This ID is already assigned to another bulk payment']);
      CRM_Utils_System::civiExit(1);
    }

    civicrm_api3('Contribution', 'setvalue', [
      'field' => 'check_number',
      'id' => $params['coid'],
      'value' => $params['cnum'],
    ]);
  }

}
