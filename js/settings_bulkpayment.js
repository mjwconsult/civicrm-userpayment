CRM.$(function($) {
  showHideRedirectOptions($('input[name="userpayment_paymentadd_redirect"]:checked').val());

  $('input[name="userpayment_paymentadd_redirect"]').on('change', function(event) {
    showHideRedirectOptions(parseInt(this.value));
  });

  function showHideRedirectOptions(value) {
    if (value === 0) {
      $('tr.crm--form-block-userpayment_paymentadd_redirecturl').hide();
      $('tr.crm--form-block-userpayment_paymentadd_redirectthankyou').show();
    }
    else {
      $('tr.crm--form-block-userpayment_paymentadd_redirecturl').show();
      $('tr.crm--form-block-userpayment_paymentadd_redirectthankyou').hide();
    }
  }
});
