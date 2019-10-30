<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * This class is used to create a form at: civicrm/user/payment/collect?reset=1&cid=202&id=bulk1
 * The form allows you to collect a number of contributions together and create a single "bulk" one that will
 *   pay them all off
 * Class CRM_Userpayment_Form_CollectPayments
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

    $this->assign('bulkIdentifier', CRM_Utils_Request::retrieveValue('id', 'String', NULL, TRUE));

    // We can access this if the contact has edit permissions and provided a valid checksum
    if (!CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->getContactID(), $this)) {
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    $this->assign('contactId', $this->getContactID());
    $intro = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentcollect_introduction'));
    $this->assign('introduction', $intro);

    $this->setTitle(\Civi::settings()->get('userpayment_paymentcollect_title'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('%1', [1 => ts('Submit')]),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    $this->add('hidden', 'id', 'Bulk Identifier')->freeze();
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults['id'] = CRM_Utils_Request::retrieveValue('id', 'String', NULL, TRUE);
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // contributions have already been created.
    $bulkIdentifier = $submittedValues['id'];

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
      'financial_type_id' => \Civi::settings()->get('userpayment_bulkfinancialtype'),
      'total_amount' => $amounts['total_amount'],
      'tax_amount' => $amounts['tax_amount'],
      'fee_amount' => $amounts['fee_amount'],
      'contribution_status_id' => "Pending",
      'contact_id' => "user_contact_id",
      'check_number' => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
    ];

    // Do we already have a contribution for this bulk payment?
    try {
      $masterContribution = civicrm_api3('Contribution', 'getsingle', [
        'return' => ["contribution_status_id", "id"],
        'check_number' => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
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

}
