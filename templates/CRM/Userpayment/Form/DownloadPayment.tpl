{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-payment-form-block">

  <h3>Hello {$displayName}</h3>
    {if $introduction}
      <div class="section-description">{$introduction}</div>
    {/if}

  <div class="crm-section crm-payment-form-block-amount">
    <fieldset id="invoice-summary">
      <div class="crm-public-form-item crm-section invoice-reference-section">
        <div class="label">{ts}Invoice Reference{/ts}</div>
        <div class="content">{$invoiceReference}</div>
      </div>
      <div class="crm-public-form-item crm-section invoice-reference-section">
        <div class="label">{$form.total_amount.label}</div>
        <div class="content">{$contributionBalance.total|crmMoney:$contributionBalance.currency}</div>
      </div>
    </fieldset>
  </div>

    {strip}
      <table class="selector">
        <tr class="columnheader">
            {foreach from=$headers item=header}
              <th>{$header}</th>
            {/foreach}
        </tr>
          {foreach from=$rows item=row}
            <tr id='rowid{$row.contribution_id}' class="{cycle values="odd-row,even-row"}">
              <td class="crm-downloadpayment_contribution_id">{$row.contribution_id}</td>
              <td class="crm-downloadpayment_name">{$row.name}</td>
              <td class="crm-downloadpayment_amount">{$row.amount}</td>
              <td class="crm-downloadpayment_description">{$row.description}</td>
            </tr>
          {/foreach}
      </table>
    {/strip}

  <div id="payment-processor">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>

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

