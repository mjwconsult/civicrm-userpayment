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
    <td class="crm-pledge-pledge_amount">{$row.contribution_id}</td>
    <td class="crm-pledge-pledge_total_paid">{$row.name}</td>
    <td class="crm-pledge-pledge_amount">{$row.amount}</td>
    <td class="crm-pledge-pledge_contribution_type">{$row.description}</td>
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

