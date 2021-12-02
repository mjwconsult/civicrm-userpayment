<?php
/**
 * https://civicrm.org/licensing
 */

use CRM_Userpayment_ExtensionUtil as E;

/**
 * This form collects and processes the "bulk" payment.
 * The previous form generates a contribution linked to a collection of contributions.
 */
class CRM_Userpayment_Form_BulkPayment extends CRM_Userpayment_Form_Payment {

  /**
   * @var array of payment params
   */
  protected $_params = [];

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    if (!$this->getContactID()) {
      \Civi::log()->error('Missing contactID for user/payment/bulk');
      throw new CRM_Core_Exception(E::ts('You do not have permission to access this page.'));
    }
    if (!$this->getContributionID()) {
      \Civi::log()->error('Missing contributionID for user/payment/bulk');
      throw new CRM_Core_Exception(E::ts('You do not have permission to access this page.'));
    }

    // We can access this if the contact has edit permissions and provided a valid checksum
    if (!CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->getContactID(), $this)) {
      throw new CRM_Core_Exception(E::ts('You do not have permission to access this page.'));
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
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentbulk_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentbulk_title'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, FALSE);
    // We don't allow the pay later processor when making a payment
    unset($this->_processors[0]);
    $this->add('select', 'payment_processor_id', E::ts('Payment Processor'), $this->_processors, NULL);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');

    $label = E::ts('Payment Amount');
    $totalAmountField = $this->addMoney('total_amount',
      $label,
      TRUE,
      $attributes['total_amount'],
      FALSE, 'currency', NULL
    );
    $totalAmountField->freeze();

    $this->addField('trxn_date', ['entity' => 'FinancialTrxn', 'label' => E::ts('Date Received'), 'context' => 'Contribution'], FALSE, FALSE);
    $this->add('hidden', 'coid');

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('component', $this->_component);
    $this->assign('email', $this->_contributorEmail);

    // required by processBillingAddress. Otherwise it creates a duplicate contact
    $this->_contributorContactID = $this->getContactID();

    if (\Civi::settings()->get('userpayment_paymentbulk_captcha') && !CRM_Core_Session::getLoggedInContactID()) {
      // Add reCAPTCHA
      if (is_callable(['CRM_Utils_ReCAPTCHA', 'enableCaptchaOnForm'])) {
        CRM_Utils_ReCAPTCHA::enableCaptchaOnForm($this);
      }
    }

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => E::ts('Make Payment'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    $this->addFormRule(['CRM_Userpayment_Form_BulkPayment', 'formRule'], $this);
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
    $this->submit($submittedValues);

    // Redirect based on user preference
    $redirectMode = (int) \Civi::settings()->get('userpayment_paymentbulk_redirect');
    switch ($redirectMode) {
      case self::PAYMENT_REDIRECT_THANKYOU:
        $url = CRM_Utils_System::url('civicrm/user/payment/thankyou', "coid={$this->getContributionID()}&cid={$this->getContactID()}");
        break;

      case self::PAYMENT_REDIRECT_URL:
        $url = \Civi::settings()->get('userpayment_paymentbulk_redirecturl');
        if (empty(parse_url($url)['host']) && (strpos($url, '/') !== 0)) {
          $url = '/' . $url;
        }
    }

    CRM_Utils_System::redirect($url);
  }

  /**
   * Process Payments.
   * @param array $submittedValues
   *
   */
  public function submit($submittedValues) {
    $this->_params = $submittedValues;
    $this->beginPostProcess();
    $this->processBillingAddress();
    $this->processCreditCard();

    if ($this->_params['payment_status_id'] !== CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
      Civi::log()->debug('Contribution was not completed');
      CRM_Core_Error::statusBounce('Contribution was not completed');
      return;
    }

    // Update all the linked contributions
    // contributions have already been created.
    if (empty($this->getContributionID())) {
      Civi::log()->debug('No contribution ID');
      CRM_Core_Error::statusBounce('No contribution ID!');
    }

    // Get the bulk identifier that links these together
    $masterBulkIdentifier = (string) civicrm_api3('Contribution', 'getvalue', [
      'return' => CRM_Userpayment_BulkContributions::getIdentifierFieldName(),
      'id' => $this->getContributionID(),
    ]);
    $bulkIdentifier = CRM_Userpayment_BulkContributions::getBulkIdentifierFromMaster($masterBulkIdentifier);

    // Get all contributions with a bulk identifier matching the one specified on the form
    $contributions = CRM_Userpayment_BulkContributions::getContributionsForBulkIdentifier($bulkIdentifier);

    try {
      foreach ($contributions as $contributionID => $contributionDetail) {
        // Create a payment for each of these bulk contributions
        civicrm_api3('Payment', 'create', [
            'contribution_id' => $contributionID,
            'total_amount' => $contributionDetail['total_amount'],
            'payment_instrument_id' => 'Bulk Payment',
            'trxn_id' => "{$bulkIdentifier}_{$contributionID}",
          ]
        );
      }
    }
    catch (Exception $e) {
      \Civi::log()->debug('BulkPayment error creating payments: ' . $e->getMessage());
    }
    finally {
      // Always create the payment record on the contribution - as this is a "real" contribution we record even if there were failures above
      $paymentParams = [
        'contribution_id' => $this->getContributionID(),
        'total_amount' => $this->_params['total_amount'],
        'fee_amount' => $this->_params['fee_amount'] ?? 0,
        'trxn_id' => $this->_params['trxn_id'] ?? '',
        'trxn_date' => $this->_params['trxn_date'] ?? date('Y-m-d H:i:s'),
        'payment_processor_id' => $this->_params['payment_processor_id'],
        'is_send_contribution_notification' => FALSE,
        'skipCleanMoney' => TRUE,
      ];
      $paymentID = civicrm_api3('Payment', 'create', $paymentParams)['id'];
    }

    $statusMsg = E::ts('The payment record has been processed.');
    // send email
    if (!empty($paymentID) && !empty(\Civi::settings()->get('userpayment_paymentbulk_emailreceipt'))) {
      // @todo sort out receipts
      $sendResult = civicrm_api3('Payment', 'sendconfirmation', ['id' => $paymentID])['values'][$paymentID];
      if ($sendResult['is_sent']) {
        $statusMsg .= ' ' . E::ts('A receipt has been emailed to the contributor.');
      }
    }

    CRM_Core_Session::setStatus($statusMsg, E::ts('Saved'), 'success');
  }

  public function processCreditCard() {
    $now = date('YmdHis');

    $this->_params['amount'] = $this->_params['total_amount'];
    $this->_params['currencyID'] = $this->contributionBalance['currency'];

    if (!empty($this->_params['trxn_date'])) {
      $this->_params['receive_date'] = $this->_params['trxn_date'];
    }

    if (empty($this->_params['receive_date'])) {
      $this->_params['receive_date'] = $now;
    }
    $this->_params['receipt_date'] = $now;
    $this->_params['invoiceID'] = $this->_params['invoice_id'] ?? md5(uniqid(rand(), TRUE));
    $this->_params['contactID'] = $this->getContactID();

    if (\Civi::settings()->get('userpayment_paymentbulk_emailreceipt')) {
      list($this->userDisplayName, $this->userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
      $this->_params['email'] = $this->_contributorEmail;
      $this->_params['is_email_receipt'] = TRUE;
    }
    else {
      $this->_params['is_email_receipt'] = $this->_params['is_email_receipt'] = FALSE;
    }

    if ($this->_params['amount'] > 0.0) {
      try {
        $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
        $paymentParams = $this->_params;
        $result = $payment->doPayment($paymentParams);
        $this->_params = array_merge($this->_params, $result);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        Civi::log()->error('Payment processor exception: ' . $e->getMessage());
        CRM_Core_Error::statusBounce($e->getMessage());
      }
    }

    $this->set('params', $this->_params);
  }

}
