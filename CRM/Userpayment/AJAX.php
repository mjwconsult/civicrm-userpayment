<?php

class CRM_Userpayment_AJAX {

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

  public static function addBulkContribution() {
    $requiredParameters = [
      'coid' => 'Positive',
      'cnum' => 'String',
    ];
    $params = CRM_Core_Page_AJAX::validateParams($requiredParameters);

    $existingCheckNumber = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'check_number',
      'id' => $params['coid'],
    ]);

    if ($existingCheckNumber === $params['cnum']) {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode(['message' => "You have already added {$params['coid']} to this bulk payment!"]);
      CRM_Utils_System::civiExit(1);
    }
    if (!empty($existingCheckNumber)) {
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      echo json_encode(['message' => 'This ID is already assigned to another bulk payment']);
      CRM_Utils_System::civiExit(1);
    }

    $contribution = civicrm_api3('Contribution', 'setvalue', [
      'field' => 'check_number',
      'id' => $params['coid'],
      'value' => $params['cnum'],
    ]);
  }

}
