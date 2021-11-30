<?php
/**
 * https://civicrm.org/licensing
 */

use Civi\Api4\Contribution;

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
      'cnum' => 'String',
    ];
    $params = CRM_Core_Page_AJAX::validateParams($requiredParameters);

    $contributionParams = [
      'entity_id' => $params['coid'],
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => 'null',
    ];

    $transaction = new CRM_Core_Transaction();
    // Remove from the bulk contribution (decrease the amount etc).
    CRM_Userpayment_BulkContributions::removeFromBulkContribution($contributionParams['entity_id'], $params['cnum']);

    // We use CustomValue.create instead of Contribution.create because Contribution.create is way too slow
    civicrm_api3('CustomValue', 'create', $contributionParams);
    $transaction->commit();
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

      $existingContribution = Contribution::get(FALSE)
        ->addSelect('*', 'bulk_payments.identifier')
        ->addWhere('id', '=', $params['coid'])
        ->execute()
        ->first();
    }
    catch (Exception $e) {
      self::returnAjaxError('ID does not exist!');
    }

    $masterContribution = CRM_Userpayment_BulkContributions::getMasterContribution($params['cnum']);
    if (CRM_Userpayment_BulkContributions::isMasterContributionCompleted($masterContribution)) {
      self::returnAjaxError("Cannot add to a bulk payment that has already been paid!");
    }
    // Don't add a second time
    if ($existingContribution[CRM_Userpayment_BulkContributions::getIdentifierFieldName()] === $params['cnum']) {
      self::returnAjaxError("You have already added {$params['coid']} to this bulk payment!");
    }
    // Only allow adding pending contributions
    if ((int)$existingContribution['contribution_status_id'] !== (int)CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
      self::returnAjaxError("Cannot add payment {$params['coid']} because it is not in \"Pending\" status");
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
    http_response_code(400);
    exit(1);
  }

}
