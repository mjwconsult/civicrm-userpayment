<?php
/**
 * https://civicrm.org/licensing
 */

class CRM_Userpayment_Form_CollectPayments extends CRM_Userpayment_Form_Payment {

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    $this->getContactID();

    // We can access this if the contact has edit permissions and provided a valid checksum
    if (!CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->getContactID(), $this)) {
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    $this->assign('contactId', $this->getContactID());
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentadd_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentadd_title'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('%1', [1 => ts('Make Payment')]),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    CRM_Core_Payment_Form::setDefaultValues($this, $this->getContactID());
    $defaults = array_merge($defaults, $this->_defaults);

    if (empty($defaults['trxn_date'])) {
      $defaults['trxn_date'] = date('Y-m-d H:i:s');
    }

    $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->getContributionBalance()['balance']);
    $defaults['coid'] = $this->getContributionID();

    $defaults['payment_processor_id'] = $this->_paymentProcessor['id'];

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // contributions have already been created.
    $bulkIdentifier = 'bulk1';

    // Get all contributions with a "check_number" matching the one specified on the form
    $contributions = civicrm_api3('Contribution', 'get', [
      'return' => ["id", 'total_amount', 'tax_amount', 'fee_amount'],
      'check_number' => $bulkIdentifier,
    ]);

    $amounts = ['total_amount' => 0, 'tax_amount' => 0, 'fee_amount' => 0];
    foreach (CRM_Utils_Array::value('values', $contributions) as $contributionID => $contributionDetail) {
      $amounts['total_amount'] += $contributionDetail['total_amount'];
      $amounts['tax_amount'] += $contributionDetail['tax_amount'];
      $amounts['fee_amount'] += $contributionDetail['fee_amount'];
    }
    // Create a contribution matching the total amount of all the other contributions
    $contributionParams = [
      // @fixme - allow to specify custom financial_type_id
      'financial_type_id' => "Donation",
      'total_amount' => $amounts['total_amount'],
      'tax_amount' => $amounts['tax_amount'],
      'fee_amount' => $amounts['fee_amount'],
      'contribution_status_id' => "Pending",
      'contact_id' => "user_contact_id",
      'check_number' => "BULK_{$bulkIdentifier}",
    ];
    $bulkContribution = civicrm_api3('Contribution', 'create', $contributionParams);

    $url = CRM_Utils_System::url('civicrm/user/payment/bulk', "coid={$bulkContribution['id']}&cid={$this->getContactID()}");
    CRM_Utils_System::redirect($url);

    // On payment of the bulk one:
    // We update them to have a payment_instrument_id of "Bulk Payment"
  }

}
