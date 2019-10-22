<?php
/**
 * https://civicrm.org.licensing
 */

class CRM_Userpayment_Page_Thankyou extends CRM_Core_Page {

  public function run() {
    $thankyouText = htmlspecialchars_decode(\Civi::settings()->get('userpayment_paymentadd_redirectthankyou'));
    $this->assign('thankyou_text', $thankyouText);
    return parent::run();
  }
}
