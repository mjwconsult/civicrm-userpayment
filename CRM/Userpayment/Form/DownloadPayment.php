<?php
/*
 * https://civicrm.org/licensing
 */

/**
 * This form collects and processes the "bulk" payment.
 * The previous form generates a contribution linked to a collection of contributions.
 */
class CRM_Userpayment_Form_DownloadPayment extends CRM_Userpayment_Form_Payment {

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    $this->getContactID();
    $this->getContributionID();

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
    ]);
    $this->setContributionBalance($contributionBalance['values']);

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

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);

    // Add data about the linked contributions
    if (empty($this->getContributionID())) {
      Civi::log()->debug('No contribution ID');
      CRM_Core_Error::statusBounce('No contribution ID!');
    }

    // Get the bulk identifier that links these together
    $bulkIdentifier = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'check_number',
      'id' => $this->getContributionID(),
    ]);
    $bulkIdentifier = substr($bulkIdentifier, 5);

    // Get all contributions with a "check_number" matching the one specified on the form
    $contributions = civicrm_api3('Contribution', 'get', [
      'return' => ['id', 'contact_id', 'total_amount', 'source', 'currency'],
      'check_number' => $bulkIdentifier,
    ]);

    foreach (CRM_Utils_Array::value('values', $contributions) as $contributionID => $contributionDetail) {
      $contactDisplayName = civicrm_api3('Contact', 'getvalue', [
        'return' => "display_name",
        'id' => $contributionDetail['contact_id'],
      ]);
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
