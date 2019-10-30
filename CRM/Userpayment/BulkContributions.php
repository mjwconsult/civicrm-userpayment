<?php

class CRM_Userpayment_BulkContributions {

  const MASTER_PREFIX="BULK_";

  public static function getMasterIdentifier($identifier) {
    if (strcmp($identifier, self::MASTER_PREFIX . $identifier) === 0) {
      return $identifier;
    }
    return "BULK_{$identifier}";
  }

  public static function getBulkIdentifierFromMaster($identifier) {
    return substr($identifier, 5);
  }

  /**
   * @param $params
   */
  public static function getBulkContributions($params) {
    if (empty($params['cnum'])) {
      throw new CRM_Core_Exception('Missing required parameter cnum');
    }

    // Get all contributions with a "check_number" matching the one specified on the form
    $contributions = civicrm_api3('Contribution', 'get', [
      'return' => ['id', 'contact_id', 'total_amount', 'source', 'currency'],
      'check_number' => $params['cnum'],
    ]);

    $sum = 0;
    foreach (CRM_Utils_Array::value('values', $contributions) as $contributionID => $contributionDetail) {
      $contactDisplayName = civicrm_api3('Contact', 'getvalue', [
        'return' => "display_name",
        'id' => $contributionDetail['contact_id'],
      ]);
      $row = [
        'DT_RowId' => $contributionDetail['id'],
        'DT_RowAttr' => ['data-entity' => 'contribution', 'data-id' => $contributionDetail['id']],
        'DT_RowClass' => 'crm-entity',
        'contribution_id' => $contributionDetail['id'],
        'name' => $contactDisplayName,
        'amount' => CRM_Utils_Money::format($contributionDetail['total_amount'], $contributionDetail['currency']),
        'description' => CRM_Userpayment_BulkContributions::getContributionDescription($contributionDetail),
        'links' => "<a href='#' data-id='{$contributionDetail['id']}' class='collect-remove' onclick='window.collectPayments.removeItem(this)'>Remove</a>",
      ];
      $sum += $contributionDetail['total_amount'];
      $rows[] = $row;
    }
    // Add a "sum" row
    $rows[] = [
      'DT_RowId' => NULL,
      'DT_RowAttr' => ['data-entity' => 'contribution', 'data-id' => NULL],
      'DT_RowClass' => 'crm-entity',
      'contribution_id' => '',
      'name' => '',
      'amount' => '<strong>' . CRM_Utils_Money::format($sum, $contributionDetail['currency']) . '</strong>',
      'description' => '<strong>Total to pay</strong>',
      'links' => NULL,
    ];
    return $rows;
  }

  public static function getContributionDescription($contribution) {
    if (!empty($contribution['source'])) {
      return $contribution['source'];
    }
    $participantPayments = civicrm_api3('ParticipantPayment', 'get', [
      'return' => ["participant_id.event_id.title"],
      'contribution_id' => $contribution['id'],
    ]);
    if (!empty($participantPayments['values'])) {
      return CRM_Utils_Array::first($participantPayments['values'])["participant_id.event_id.title"];
    }
    return 'Contribution';
  }
}
