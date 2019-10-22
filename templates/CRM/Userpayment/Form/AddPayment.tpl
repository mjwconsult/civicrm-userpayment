{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-payment-form-block">

  <h3>Hello {$displayName}</h3>
    {if $introduction}
      <div class="section-description">{$introduction}</div>
    {/if}

  <div class="crm-section crm-payment-form-block-amount">
    <fieldset id="amount">
    <div class="label">{$form.total_amount.label}</div>
    <div class="content">
    <span id='totalAmount'>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.total_amount.html|crmAddClass:eight}</span>
    &nbsp; <span class="status">{ts}Balance Owed{/ts}: {$contributionBalance.balance|crmMoney:$contributionBalance.currency}</span>
    </div>
    </fieldset>
    <div id="payment-errors" role="alert" class="alert alert-danger hidden"></div>
  </div>
  <div id="payment-processor">
      <div class="crm-section crm-payment-form-block-payment_processor_id">
          <fieldset class="payment-processor-type">
              <div class="label">{$form.payment_processor_id.label}<span class="crm-marker"> * </span></div>
              <div class="content">{$form.payment_processor_id.html}</div>
          </fieldset>
      </div>

    <div class="crm-section crm-payment-form-block-billingblock" id="paymentDetails_Information">
        {include file='CRM/Core/BillingBlockWrapper.tpl'}
    </div>

    <div class="clear" />
      {if $isCaptcha}{include file='CRM/common/ReCAPTCHA.tpl'}{/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>

    {include file="CRM/Form/validate.tpl"}
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    window.onbeforeunload = null;
    checkAmount();
    $('input#total_amount').on('input', function() { checkAmount(); });

    function checkAmount() {
      if ($('input#total_amount').val() <= 0) {
        $('div#payment-processor').hide();
        $('div#payment-errors').text('You must specify an amount greater than 0 to make a payment!');
        $('div#payment-errors').removeClass('hidden');
      }
      else {
        $('div#payment-errors').addClass('hidden');
        $('div#payment-errors').text('');
        $('div#payment-processor').show();
      }
    }
  });
</script>
{/literal}

