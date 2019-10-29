{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-payment-form-block">

  <h3>Hello {$displayName}</h3>
    {if $introduction}
      <div class="section-description">{$introduction}</div>
    {/if}

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

