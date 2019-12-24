<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * This class is used to create a form at: civicrm/user/payment/relation?reset=1&cid=202
 * The form allows you to allocate a payment to a number of contacts linked by relationship to the primay (cid=)

 * Class CRM_Userpayment_Form_RelationPayments
 */
class CRM_Userpayment_Form_RelationPayments extends CRM_Userpayment_Form_Payment {

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
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentcollect_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentcollect_title'));

    $this->assign('listOfPayments', $this->getListOfPayments());
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('%1', [1 => ts('Next')]),
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
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // contributions have already been created.
    $bulkIdentifier = $submittedValues['id'];

    // Get all contributions with a bulk identifier matching the one specified on the form
    $contributions = civicrm_api3('Contribution', 'get', [
      'return' => ["id", 'total_amount', 'tax_amount', 'fee_amount'],
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => $bulkIdentifier,
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
      'financial_type_id' => \Civi::settings()->get('userpayment_bulkfinancialtype'),
      'total_amount' => $amounts['total_amount'],
      'tax_amount' => $amounts['tax_amount'],
      'fee_amount' => $amounts['fee_amount'],
      'contribution_status_id' => "Pending",
      'contact_id' => "user_contact_id",
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
    ];

    // Do we already have a contribution for this bulk payment?
    try {
      $masterContribution = civicrm_api3('Contribution', 'getsingle', [
        'return' => ["contribution_status_id", "id"],
        CRM_Userpayment_BulkContributions::getIdentifierFieldName() => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
      ]);
      $contributionParams['id'] = $masterContribution['id'];
      if ((int) $masterContribution['contribution_status_id'] !== (int) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
        CRM_Core_Error::statusBounce('A bulk contribution already exists and is not in Pending state');
      }
    }
    catch (Exception $e) {
      // do nothing
    }
    $bulkContribution = civicrm_api3('Contribution', 'create', $contributionParams);

    $url = CRM_Utils_System::url(\Civi::settings()->get('userpayment_paymentcollect_redirecturl'), "coid={$bulkContribution['id']}&cid={$this->getContactID()}");
    CRM_Utils_System::redirect($url);
  }

  public function getListOfPayments() {
    // @todo
    $this->amount['member'] = 12;
    $this->amount['donation'] = 20;

    $contactIDs = [200, 201, 202, 203];

    foreach ($contactIDs as $contactID) {
      $displayName = civicrm_api3('Contact', 'getvalue', ['id' => $contactID, 'return' => 'display_name']);
      $list[$contactID] = ['name' => $displayName, 'amount' => $this->amount['member']];
    }
    $list[0] = ['name' => 'Donation', 'amount' => $this->amount['donation']];

    return $list;
  }

}
