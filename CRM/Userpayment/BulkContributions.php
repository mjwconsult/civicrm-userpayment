<?php
/**
 * https://civicrm.org/licensing
 */

use Civi\Api4\Contact;
use Civi\Api4\Contribution;

/**
 * Generic helper class for CRM_Userpayment
 * Class CRM_Userpayment_BulkContributions
 */
class CRM_Userpayment_BulkContributions {

  // The prefix appended to bulk identifier on the "master" contribution
  const MASTER_PREFIX="BULK_";

  // The various supported name formats
  const PAYMENT_NAMEFORMAT_FULL = 0;
  const PAYMENT_NAMEFORMAT_INITIALS = 1;

  // Custom data identifiers
  const CUSTOMGROUP="bulk_payments";
  const CUSTOMFIELD_IDENTIFIER="identifier";

  /**
   * Return the custom field name for use in api3 calls
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function getIdentifierFieldName() {
    return CRM_Mjwshared_Utils::getCustomByName(self::CUSTOMFIELD_IDENTIFIER, self::CUSTOMGROUP);
  }

  /**
   * Return the identifier for the master contribution when given the identifier for the bulk contributions
   * @param string $identifier
   *
   * @return string
   */
  public static function getMasterIdentifier($identifier) {
    if (strcmp($identifier, self::MASTER_PREFIX . self::getBulkIdentifierFromMaster($identifier)) === 0) {
      return $identifier;
    }
    return self::MASTER_PREFIX . "{$identifier}";
  }

  /**
   * Return the identifier for the bulk contributions when given the identifier of the master contribution
   * @param string $identifier
   *
   * @return string
   */
  public static function getBulkIdentifierFromMaster($identifier) {
    return substr($identifier, 5);
  }

  /**
   * @param string $bulkIdentifier without the BULK_ prefix.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getMasterContribution(string $bulkIdentifier): array {
    return Contribution::get(FALSE)
      ->addSelect('*', 'bulk_payments.identifier')
      ->addWhere('bulk_payments.identifier', '=', CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier))
      ->execute()
      ->first() ?? [];
  }

  /**
   * @param array $masterContribution
   *
   * @return bool
   */
  public static function isMasterContributionCompleted(array $masterContribution): bool {
    if (empty($masterContribution)) {
      // Not yet created
      return FALSE;
    }
    return ((int)$masterContribution['contribution_status_id'] === (int)CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'));
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

    // Get all contributions with a bulk identifier matching the one specified on the form
    $contributions = self::getContributionsForBulkIdentifier($params['cnum']);
    $masterContribution = self::getMasterContribution($params['cnum']);
    $paid = FALSE;
    if (self::isMasterContributionCompleted($masterContribution)) {
      $paid = TRUE;
    }

    $currency = $masterContribution['currency'] ?? CRM_Core_Config::singleton()->defaultCurrency;

    $sum = 0;
    foreach ($contributions as $contributionDetail) {
      $contactDisplayName = CRM_Userpayment_BulkContributions::getFormattedDisplayName($contributionDetail['contact_id']);
      $row = [
        'DT_RowId' => $contributionDetail['id'],
        'DT_RowAttr' => ['data-entity' => 'contribution', 'data-id' => $contributionDetail['id']],
        'DT_RowClass' => 'crm-entity',
        'contribution_id' => $contributionDetail['id'],
        'name' => $contactDisplayName,
        'amount' => CRM_Utils_Money::format($contributionDetail['total_amount'], $contributionDetail['currency']),
        'description' => CRM_Userpayment_BulkContributions::getContributionDescription($contributionDetail),
        'links' => $paid ? '' : "<a href='#' data-id='{$contributionDetail['id']}' class='collect-remove' onclick='window.collectPayments.removeItem(this)'>Remove</a>",
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
      'amount' => '<strong>' . CRM_Utils_Money::format($sum, $currency) . '</strong>',
      'description' => $paid ? '<strong>Total paid</strong>' : '<strong>Total to pay</strong>',
      'links' => NULL,
    ];
    return $rows;
  }

  /**
   * Return a suitable description for a contribution to be displayed on lists
   * @param array $contribution
   *
   * @return string
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
   * @return string
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getFormattedDisplayName($contactID) {
    switch ((int)\Civi::settings()->get('userpayment_nameformat')) {
      case CRM_Userpayment_BulkContributions::PAYMENT_NAMEFORMAT_FULL:
        return Contact::get(FALSE)
          ->addSelect('display_name')
          ->addWhere('id', '=', $contactID)
          ->execute()
          ->first()['display_name'];

      case CRM_Userpayment_BulkContributions::PAYMENT_NAMEFORMAT_INITIALS:
        $initialsFields = ['first_name', 'middle_name', 'last_name'];
        $contactDetails = Contact::get(FALSE)
          ->addSelect('first_name', 'middle_name', 'last_name')
          ->addWhere('id', '=', $contactID)
          ->execute()
          ->first();
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

  /**
   * Remove a contribution from a bulk contribution (clear the bulk identifier field).
   * Called via userpayment_civicrm_post
   *
   * @param int $contributionId
   * @param string $bulkIdentifier - the client bulk identifier
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function removeFromBulkContribution($contributionId, $bulkIdentifier) {
    if (isset(Civi::$statics[__CLASS__]['removebulkcontribution'])) {
      return;
    }
    Civi::$statics[__CLASS__]['removebulkcontribution'] = TRUE;

    // We must have a bulk identifier
    if (empty($bulkIdentifier)) {
      return;
    }

    if (CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier) === $bulkIdentifier) {
      // This should never happen but if we get passed a bulkIdentifier for a master contribution we should reject
      return;
    }

    $contribution = Contribution::get(FALSE)
      ->addSelect('*', 'bulk_payments.identifier')
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->first();

    // This checks if a contribution has been paid and is part of a bulk contribution
    // If so, the bulk contribution is reduced by that amount and the bulk identifier removed

    // We have a completed contribution with a bulk identifier - does it have a corresponding bulk contribution?
    try {
      $masterContribution = Contribution::get(FALSE)
        ->addSelect('*', 'bulk_payments.identifier')
        ->addWhere('bulk_payments.identifier', '=', CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier))
        ->execute()
        ->first();

      if (empty($contribution['total_amount'])) {
        return;
      }
      // If the master contribution is completed don't touch
      if ((int)$masterContribution['contribution_status_id'] === (int)CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
        return;
      }

      // Reduce the bulk contribution by the amount that's been paid. Remove the bulk identifier from the linked contribution.
      // Recalculate the amounts (@todo this should really be shared with CRM_Userpayment_Form_CollectPayments::postProcess)
      $contributions = CRM_Userpayment_BulkContributions::getContributionsForBulkIdentifier($bulkIdentifier);

      $masterAmounts = ['total_amount' => 0, 'tax_amount' => 0, 'fee_amount' => 0];
      foreach ($contributions as $contributionDetail) {
        $masterAmounts['total_amount'] += ((float) $contributionDetail['total_amount'] ?? 0);
        $masterAmounts['tax_amount'] += ((float) $contributionDetail['tax_amount'] ?? 0);
        $masterAmounts['fee_amount'] += ((float) $contributionDetail['fee_amount'] ?? 0);
      }

      Contribution::update(FALSE)
        ->addValue('total_amount', $masterAmounts['total_amount'])
        ->addValue('tax_amount', $masterAmounts['tax_amount'])
        ->addValue('fee_amount', $masterAmounts['fee_amount'])
        ->addWhere('id', '=', $masterContribution['id'])
        ->execute();
    }
    catch (Exception $e) {
      // We've either not found one or there is more than one. Don't handle it.
      return;
    }
  }

  /**
   * @param int $masterContributionID
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function removeFromDeletedBulkContribution($masterContributionID) {
    if (isset(Civi::$statics[__CLASS__]['removefromdeletedbulkcontribution'])) {
      return;
    }
    Civi::$statics[__CLASS__]['removedeletedbulkcontribution'] = TRUE;

    // This checks if a contribution has been paid and is part of a bulk contribution
    // If so, the bulk contribution is reduced by that amount and the bulk identifier removed
    $masterContribution = Contribution::get(FALSE)
      ->addSelect('*', 'bulk_payments.identifier')
      ->addWhere('id', '=', $masterContributionID)
      ->execute()
      ->first();
    if (empty($masterContribution['bulk_payments.identifier'])) {
      return;
    }

    // We have a completed contribution with a bulk identifier - does it have a corresponding bulk contribution?
    try {
      $bulkContributions = Contribution::get(FALSE)
        ->addSelect('*', 'bulk_payments.identifier')
        ->addWhere('bulk_payments.identifier', '=', CRM_Userpayment_BulkContributions::getBulkIdentifierFromMaster($masterContribution['bulk_payments.identifier']))
        ->execute();
      foreach ($bulkContributions as $contributionID => $contributionDetail) {
        // Loop through all contributions linked to the master and remove the bulk identifier
        $groupID = civicrm_api3('CustomGroup', 'getvalue', [
          'return' => "id",
          'name' => "bulk_payments",
        ]);
        $contributionParams = [
          'entity_id' => $contributionID,
          CRM_Mjwshared_Utils::getCustomByName('identifier', $groupID) => 'null',
        ];
        // We use CustomValue.create instead of Contribution.create because Contribution.create is way too slow
        civicrm_api3('CustomValue', 'create', $contributionParams);
      }
    }
    catch (Exception $e) {
      // We've either not found one or there is more than one. Don't handle it.
      \Civi::log()->error('removeFromDeletedBulkContribution: ' . $e->getMessage());
      return;
    }
  }

  /**
   * @param string $bulkIdentifier
   *
   * @return array
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getContributionsForBulkIdentifier($bulkIdentifier) {
    // Get all contributions with a bulk identifier matching the one specified on the form
    $contributions = Contribution::get(FALSE)
      ->addSelect('*', 'bulk_payments.identifier')
      ->addWhere('bulk_payments.identifier', '=', $bulkIdentifier)
      ->execute();
    if (empty($contributions->count())) {
      return [];
    }

    $result = [];
    foreach ($contributions as $contribution) {
      $contributionBalance = civicrm_api3('Contribution', 'getbalance', [
        'id' => $contribution['id'],
      ])['values'];
      $contributionBalance['total_amount'] = $contributionBalance['total'];
      $contributionBalance['tax_amount'] = $contributionBalance['tax_amount'] ?? 0;
      $result[$contribution['id']] = array_merge($contribution, $contributionBalance);
      $result[$contribution['id']]['tax_amount'] = $contribution['tax_amount'] ?? 0;
    }

    return $result;
  }

}
