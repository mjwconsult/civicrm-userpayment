{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-payment-form-block">

  <h3>Hello {$displayName}</h3>
    {if $introduction}
      <div class="section-description">{$introduction}</div>
    {/if}

    <div class="section-bulk-identifier">
      <label for="bulk-identifier">Bulk Identifier:</label> <span id="bulk-identifier">{$bulkIdentifier}</span>
    </div>
  {$form.id.html}
    <div class="section-add-contribution">
    <label for="add-contribution-id">Add Payment Number:</label> <input type="text" id="add-contribution-id" />
      <input type="button" value="Add" id="btn-add-contribution" onclick="window.collectPayments.add()"/>
    </div>
    {strip}
      <table class="collectpayment-selector crm-ajax-table" data-page-length='25'>
        <thead>
        <tr>
          <th data-data="contribution_id" class="crm-collectpayments_contribution_id">{ts}Contribution ID{/ts}</th>
          <th data-data="name" class="crm-collectpayments_name">{ts}Name{/ts}</th>
          <th data-data="amount" class="crm-collectpayments_amount">{ts}Amount{/ts}</th>
          <th data-data="description" class="crm-collectpayments_description">{ts}Description{/ts}</th>
          <th data-data="links" data-orderable="false" class="crm-collectpayments-links">&nbsp;</th>
        </tr>
        </thead>
      </table>

      <td class="crm-downloadpayment_contribution_id">{$row.contribution_id}</td>
      <td class="crm-downloadpayment_name">{$row.name}</td>
      <td class="crm-downloadpayment_amount">{$row.amount}</td>
      <td class="crm-downloadpayment_description">{$row.description}</td>

    {literal}
      <script type="text/javascript">
        (function($) {
          var selectorClass = '.collectpayment-selector';
          var bulkIdentifier = $('span#bulk-identifier').text();

          $('#CollectPayments').on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
              e.preventDefault();
              return false;
            }
          });

          CRM.$('table' + selectorClass).data({
            "ajax": {
              "url": {/literal}'{crmURL p="civicrm/ajax/collectpayments" h=0 q="snippet=4"}'{literal},
              "data": function (d) {
                d.cnum = bulkIdentifier
              }
            },
            "drawCallback": function(settings) {
              var count = 0;
              if (settings.aoData.length > 0) {
                count = settings.aoData.length - 1;
              }
              CRM.$('#DataTables_Table_0_info').text('Number of payments selected: ' + count);
            }

          });

          var collectPayments = {
            removeItem: function(event) {
              var dataId = $(event).attr("data-id");
              var URL = CRM.url('civicrm/ajax/collectpayments/remove', {coid: dataId});
              $.ajax({
                url: URL,
                success: function (data, status) {
                  $('table' + selectorClass).DataTable().draw();
                },
                error: function (data, status) {
                  if (typeof data.responseJSON.message !== 'undefined') {
                    alert(data.responseJSON.message);
                  }
                  else {
                    alert("An error occurred while removing the contribution");
                  }
                }
              });
            },
            add: function(event) {
              $('#btn-add-contribution').attr('disabled', true);
              var dataId = $('#add-contribution-id').val();
              var URL =  CRM.url('civicrm/ajax/collectpayments/add', {coid: dataId, cnum: bulkIdentifier});
              $.ajax({
                url: URL,
                success: function(data, status) {
                  $('table' + selectorClass).DataTable().draw();
                  $('#btn-add-contribution').removeAttr('disabled');
                },
                error: function(data, status) {
                  if (typeof data.responseJSON.message !== 'undefined') {
                    alert(data.responseJSON.message);
                  }
                  else {
                    alert("An error occurred while adding the contribution");
                  }
                  $('#btn-add-contribution').removeAttr('disabled');
                }
              });
            }
          };
          window.collectPayments = collectPayments;
        })(CRM.$);
      </script>
    {/literal}

    {/strip}

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

