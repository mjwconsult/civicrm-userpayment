<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Generic helper class for CRM_Userpayment
 * Class CRM_Userpayment_BulkContributions
 */
class CRM_Userpayment_BulkContributions {

  // The prefix appended to check_number on the "master" contribution
  const MASTER_PREFIX="BULK_";

  // The various supported name formats
  const PAYMENT_NAMEFORMAT_FULL = 0;
  const PAYMENT_NAMEFORMAT_INITIALS = 1;

  /**
   * Return the identifier for the master contribution when given the identifier for the bulk contributions
   * @param string $identifier
   *
   * @return string
   */
  public static function getMasterIdentifier($identifier) {
    if (strcmp($identifier, self::MASTER_PREFIX . $identifier) === 0) {
      return $identifier;
    }
    return self::MASTER_PREFIX . "{$identifier}";
  }

  /**
   * Return the identifier for the bulk contributions when given the identifier of the master contribution
   * @param string $identifier
   *
   * @return bool|string
   */
  public static function getBulkIdentifierFromMaster($identifier) {
    return substr($identifier, 5);
  }

  /**
   * Get an array of the bulk contributions
   * @param array $params
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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
      $contactDisplayName = CRM_Userpayment_BulkContributions::getFormattedDisplayName($contributionDetail['contact_id']);
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

  /**
   * Return a suitable description for a contribution to be displayed on lists
   * @param array $contribution
   *
   * @return mixed|string
   * @throws \CiviCRM_API3_Exception
   */
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

  /**
   * Return a formatted displayname for contactID based on userpayment_nameformat setting
   * @param int $contactID
   *
   * @return array|string
   * @throws \CiviCRM_API3_Exception
   */
  public static function getFormattedDisplayName($contactID) {
    switch ((int)\Civi::settings()->get('userpayment_nameformat')) {
      case CRM_Userpayment_BulkContributions::PAYMENT_NAMEFORMAT_FULL:
        return civicrm_api3('Contact', 'getvalue', [
          'return' => "display_name",
          'id' => $contactID,
        ]);

      case CRM_Userpayment_BulkContributions::PAYMENT_NAMEFORMAT_INITIALS:
        $initialsFields = ['first_name', 'middle_name', 'last_name'];
        $contactDetails = civicrm_api3('Contact', 'getsingle', [
          'return' => ['first_name', 'middle_name', 'last_name'],
          'id' => $contactID,
        ]);
        $initials = '';
        foreach ($initialsFields as $field) {
          if (!empty($contactDetails[$field])) {
            $initials .= $contactDetails[$field][0] . ' ';
          }
        }
        return trim($initials);
    }
  }

  /**
   * Format and return an invoice reference based on contribution ID and bulk identifier
   *
   * @param int $contributionID
   * @param string $bulkIdentifier
   *
   * @return string
   */
  public static function getInvoiceReference($contributionID, $bulkIdentifier) {
    return "{$contributionID}_{$bulkIdentifier}";
  }
}
