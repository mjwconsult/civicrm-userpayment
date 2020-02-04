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

    $contributionParams = [
      'entity_id' => $params['coid'],
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => 'null',
    ];
    // We use CustomValue.create instead of Contribution.create because Contribution.create is way too slow
    civicrm_api3('CustomValue', 'create', $contributionParams);
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

    try {
      $params = CRM_Core_Page_AJAX::validateParams($requiredParameters);
      $existingContribution = civicrm_api3('Contribution', 'getsingle', [
        'return' => [
          CRM_Userpayment_BulkContributions::getIdentifierFieldName(),
          'contribution_status_id'
        ],
        'id' => $params['coid'],
      ]);
    }
    catch (Exception $e) {
      self::returnAjaxError('ID does not exist!');
    }

    // Don't add a second time
    if ($existingContribution[CRM_Userpayment_BulkContributions::getIdentifierFieldName()] === $params['cnum']) {
      self::returnAjaxError("You have already added {$params['coid']} to this bulk payment!");
    }
    // Only allow adding pending contributions
    if ((int)$existingContribution['contribution_status_id'] !== (int)CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
      self::returnAjaxError("Cannot add {$params['coid']} because it is not pending payment");
    }
    if ($existingContribution[CRM_Userpayment_BulkContributions::getIdentifierFieldName()]
      === CRM_Userpayment_BulkContributions::getMasterIdentifier($params['cnum'])) {
      self::returnAjaxError('You cannot add a bulk payment to itself!');
    }
    // Don't add if it's already added to another bulk contribution
    if (!empty($existingContribution[CRM_Userpayment_BulkContributions::getIdentifierFieldName()])) {
      self::returnAjaxError('This ID is already assigned to another bulk payment');
    }

    $contributionParams = [
      'entity_id' => $params['coid'],
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => $params['cnum'],
    ];
    // We use CustomValue.create instead of Contribution.create because Contribution.create is way too slow
    civicrm_api3('CustomValue', 'create', $contributionParams);
  }

  private static function returnAjaxError($message) {
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo json_encode(['message' => $message]);
    CRM_Utils_System::civiExit(1);
  }

}
