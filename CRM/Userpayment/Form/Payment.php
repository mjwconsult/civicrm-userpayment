<?php
/**
 * https://civicrm.org/licensing
 */

use CRM_Userpayment_ExtensionUtil as E;

/**
 * This is the base class for various payment forms in UserPayment extension
 */
class CRM_Userpayment_Form_Payment extends CRM_Contribute_Form_AbstractEditPayment {

  // IDs for type of redirection
  const PAYMENT_REDIRECT_THANKYOU = 0;
  const PAYMENT_REDIRECT_URL = 1;

  protected $entity = 'Contribution';

  /**
   * @var int
   */
  protected $contactID = NULL;

  /**
   * @var int
   */
  protected $contributionID = NULL;

  /**
   * @var array
   */
  protected $contributionBalance = [];

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  /**
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getContributionID(): ?int {
    if (empty($this->contributionID)) {
      $this->contributionID = (int) CRM_Utils_Request::retrieveValue('coid', 'Positive');
    }
    if (empty($this->contributionID)) {
      $this->contributionID = $this->_params['contribution_id'] ?? $this->_params['contributionID'];
    }
    return $this->contributionID;
  }

  /**
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getContactID() {
    if (empty($this->contactID)) {
      $this->contactID = (int) CRM_Utils_Request::retrieveValue('cid', 'Positive');
      if (empty($this->contactID)) {
        $this->contactID = parent::getContactID();
      }
    }
    return $this->contactID;
  }

  /**
   * Test or live mode
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setMode() {
    $test = (bool) civicrm_api3('Contribution', 'getvalue', [
      'return' => "is_test",
      'id' => $this->getContributionID(),
    ]);
    // $this->_mode is used in various places in CiviCRM
    // Setting it allows the initial paymentprocessor to autoload
    $this->_mode = $test ? 'test' : 'live';
  }

  /**
   * @param array $contributionBalance
   */
  protected function setContributionBalance($contributionBalance) {
    $this->contributionBalance = $contributionBalance;
  }

  /**
   * @return array
   */
  protected function getContributionBalance() {
    return $this->contributionBalance;
  }

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->getContactID();
    $this->getContributionID();

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
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentadd_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentadd_title'));
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
    if ((bool)\Civi::settings()->get('userpayment_paymentadd_freezeamount')) {
      $totalAmountField->freeze();
    }

    $this->addField('trxn_date', ['entity' => 'FinancialTrxn', 'label' => E::ts('Date Received'), 'context' => 'Contribution'], FALSE, FALSE);
    $this->add('hidden', 'coid');

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('component', $this->_component);
    $this->assign('email', $this->_contributorEmail);

    // required by processBillingAddress. Otherwise it creates a duplicate contact
    $this->_contributorContactID = $this->getContactID();

    if (\Civi::settings()->get('userpayment_paymentadd_captcha') && !CRM_Core_Session::getLoggedInContactID()) {
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

    $this->addFormRule(['CRM_Userpayment_Form_AddPayment', 'formRule'], $this);
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
   * @param array $fields
   * @param $files
   * @param CRM_Userpayment_Form_Payment $form
   *
   * @return array
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    if ($fields['total_amount'] < 0) {
      $errors['total_amount'] = E::ts('Payment amount cannot negative');
    }
    if (isset($form)) {
      if (CRM_Utils_Money::subtractCurrencies(
          $fields['total_amount'],
          $form->contributionBalance['balance'],
          $form->contributionBalance['currency']) > 0
      ) {
        $errors['total_amount'] = E::ts('Payment amount cannot be greater than owed amount');
      }
      if ($form->_paymentProcessor['id'] === 0 && empty($fields['payment_instrument_id'])) {
        $errors['payment_instrument_id'] = E::ts('Payment method is a required field');
      }
    }

    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);
    $this->submit($submittedValues);

    // Redirect based on user preference
    $redirectMode = (int) \Civi::settings()->get('userpayment_paymentadd_redirect');
    switch ($redirectMode) {
      case self::PAYMENTADD_REDIRECT_THANKYOU:
        $url = CRM_Utils_System::url('civicrm/user/payment/thankyou', "coid={$this->getContributionID()}&cid={$this->getContactID()}");
        break;

      case self::PAYMENTADD_REDIRECT_URL:
        $url = \Civi::settings()->get('userpayment_paymentadd_redirecturl');
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

    $statusMsg = E::ts('The payment record has been processed.');
    // send email
    if (!empty($paymentID) && !empty(\Civi::settings()->get('userpayment_paymentadd_emailreceipt'))) {
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

    if (\Civi::settings()->get('userpayment_paymentadd_emailreceipt')) {
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
