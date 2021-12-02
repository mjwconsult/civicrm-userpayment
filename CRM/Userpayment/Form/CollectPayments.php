<?php
/**
 * https://civicrm.org/licensing
 */

use Civi\Api4\Contribution;
use CRM_Userpayment_ExtensionUtil as E;
/**
 * This class is used to create a form at: civicrm/user/payment/collect?reset=1&cid=202&id=bulk1
 * The form allows you to collect a number of contributions together and create a single "bulk" one that will
 *   pay them all off
 * Class CRM_Userpayment_Form_CollectPayments
 */
class CRM_Userpayment_Form_CollectPayments extends CRM_Userpayment_Form_Payment {

  /**
   * @var string The Bulk identifier (without BULK_)
   */
  protected $bulkIdentifier;

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    if (!$this->getContactID()) {
      \Civi::log()->error('Userpayment: Missing contactID for user/payment/collect');
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    $this->bulkIdentifier = CRM_Utils_Request::retrieveValue('id', 'String');
    if (!$this->bulkIdentifier) {
      \Civi::log()->error('Userpayment: Missing bulkIdentifier for user/payment/collect');
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }
    if (CRM_Userpayment_BulkContributions::getMasterIdentifier($this->bulkIdentifier) === $this->bulkIdentifier) {
      $this->bulkIdentifier = CRM_Userpayment_BulkContributions::getBulkIdentifierFromMaster($this->bulkIdentifier);
    }
    $this->assign('bulkIdentifier', $this->bulkIdentifier);

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
        'name' => ts('%1', [1 => ts('Next')]),
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
    $defaults['id'] = $this->bulkIdentifier;
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // contributions have already been created.
    $bulkIdentifier = $submittedValues['id'];

    $contributions = CRM_Userpayment_BulkContributions::getContributionsForBulkIdentifier($bulkIdentifier);

    $amounts = ['total_amount' => 0, 'tax_amount' => 0, 'fee_amount' => 0];
    foreach ($contributions as $contributionDetail) {
      $amounts['total_amount'] += ((float) $contributionDetail['total_amount'] ?? 0);
      $amounts['tax_amount'] += ((float) $contributionDetail['tax_amount'] ?? 0);
      $amounts['fee_amount'] += ((float) $contributionDetail['fee_amount'] ?? 0);
      $listOfIDs[] = $contributionDetail['id'] . ': ' . $contributionDetail['total_amount'];
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
      'source' => 'Bulk payment: ' . CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
      CRM_Userpayment_BulkContributions::getIdentifierFieldName() => CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier),
    ];

    // Do we already have a contribution for this bulk payment?
    try {
      $identif = CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier);
      $masterContribution = Contribution::get(FALSE)
        ->addSelect('*', 'bulk_payments.identifier')
        ->addWhere('bulk_payments.identifier', '=', CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier))
        ->execute()
        ->first();

      if (!empty($masterContribution)) {
        // We've already created a master contribution
        $contributionParams['id'] = $masterContribution['id'];
        if ((int) $masterContribution['contribution_status_id'] !== (int) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
          CRM_Core_Error::statusBounce('A bulk contribution already exists and is not in Pending state');
        }
      }
    }
    catch (Exception $e) {
      // do nothing
    }
    // Create (or update) the master contribution
    $bulkContribution = civicrm_api3('Contribution', 'create', $contributionParams);
    $bulkContribution = $bulkContribution['values'][$bulkContribution['id']];

    $note = implode(PHP_EOL, $listOfIDs);
    $note = $bulkContribution['id'] . ': ' . $bulkContribution['total_amount'] . ': ' . CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier) . PHP_EOL . $note;
    \Civi\Api4\Note::create(FALSE)
      ->addValue('entity_id', $bulkContribution['contact_id'])
      ->addValue('entity_table:name', 'Contact')
      ->addValue('note', $note)
      ->addValue('subject', CRM_Userpayment_BulkContributions::getMasterIdentifier($bulkIdentifier))
      ->execute();

    $url = CRM_Utils_System::url(\Civi::settings()->get('userpayment_paymentcollect_redirecturl'), "coid={$bulkContribution['id']}&cid={$this->getContactID()}");
    CRM_Utils_System::redirect($url);
  }

}
