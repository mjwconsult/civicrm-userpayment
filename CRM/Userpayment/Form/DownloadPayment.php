<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * This form collects and processes the "bulk" payment.
 * The previous form generates a contribution linked to a collection of contributions.
 */
class CRM_Userpayment_Form_DownloadPayment extends CRM_Userpayment_Form_Payment {

  public function getContributionID() {
    $bulkIdentifier = CRM_Utils_Request::retrieveValue('id', 'String');
    if (!empty($bulkIdentifier)) {
      try {
        $this->contributionID = civicrm_api3('Contribution', 'getvalue', ['return' => 'id', CRM_Userpayment_BulkContributions::getIdentifierFieldName() => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier)]);
      }
      catch (Exception $e) {
        // Not found. Continue and see if we can get the contribution ID another way.
      }
    }
    return parent::getContributionID();
  }

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    if (!$this->getContactID()) {
      \Civi::log()->error('Missing contactID for user/payment/bulkinvoice');
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }
    if (!$this->getContributionID()) {
      \Civi::log()->error('Missing contributionID for user/payment/bulkinvoice');
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    // We can access this if the contact has edit permissions and provided a valid checksum
    if (!CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->getContactID(), $this)) {
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    $this->setMode();

    parent::preProcess();
    $this->assign('component', $this->_component);
    $this->assign('id', $this->getContributionID());

    $contributionBalance = civicrm_api3('Contribution', 'getbalance', [
      'id' => $this->getContributionID(),
    ])['values'];
    $this->setContributionBalance($contributionBalance);

    $this->assign('contactId', $this->getContactID());
    $this->assign('contributionBalance', $this->getContributionBalance());
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentbulkinvoice_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentbulkinvoice_title'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');
    $label = ts('Payment Amount');
    $totalAmountField = $this->addMoney('total_amount',
      $label,
      TRUE,
      $attributes['total_amount'],
      FALSE, 'currency', NULL
    );
    $totalAmountField->freeze();

    $this->addField('trxn_date', ['entity' => 'FinancialTrxn', 'label' => ts('Date Received'), 'context' => 'Contribution'], FALSE, FALSE);
    $this->add('hidden', 'coid');

    // Add data about the linked contributions
    if (empty($this->getContributionID())) {
      Civi::log()->debug('No contribution ID');
      CRM_Core_Error::statusBounce('No contribution ID!');
    }

    // Get the bulk identifier that links these together
    $bulkIdentifier = CRM_Userpayment_BulkContributions::getBulkIdentifierFromMaster(civicrm_api3('Contribution', 'getvalue', [
      'return' => CRM_Userpayment_BulkContributions::getIdentifierFieldName(),
      'id' => $this->getContributionID(),
    ]));

    // Get all contributions with a bulk identifier matching the one specified on the form
    $contributions = CRM_Userpayment_BulkContributions::getContributionsForBulkIdentifier($bulkIdentifier);

    $this->assign('invoiceReference', CRM_Userpayment_BulkContributions::getInvoiceReference($this->getContributionID(), $bulkIdentifier));

    foreach ($contributions as $contributionID => $contributionDetail) {
      $contactDisplayName = CRM_Userpayment_BulkContributions::getFormattedDisplayName($contributionDetail['contact_id']);
      $row = [
        'contribution_id' => $contributionDetail['id'],
        'name' => $contactDisplayName,
        'amount' => CRM_Utils_Money::formatLocaleNumericRoundedByCurrency($contributionDetail['total_amount'], $contributionDetail['currency']),
        'description' => CRM_Userpayment_BulkContributions::getContributionDescription($contributionDetail),
      ];
      $rows[] = $row;
    }
    $headers = ['ID', 'Name', 'Amount', 'Description'];
    $this->assign('headers', $headers);
    $this->assign('rows', $rows);
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    if (empty($defaults['trxn_date'])) {
      $defaults['trxn_date'] = date('Y-m-d H:i:s');
    }

    $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->getContributionBalance()['balance']);
    $defaults['coid'] = $this->getContributionID();

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
  }

}
