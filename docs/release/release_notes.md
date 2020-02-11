# Information

Releases use the following numbering system:
**{major}.{minor}.{incremental}**

Where:
* major: Major refactoring or rewrite - make sure you read and test very carefully!
* minor: Breaking change in some circumstances, or a new feature. Read carefully and make sure you understand the impact of the change.
* incremental: A "safe" change / improvement. Should *always* be safe to upgrade.

## Release 0.11

* Workaround issues with retrieving total_amount from contribution API

## Release 0.10

* Add source description to master bulk payment. Add bulkidentifier as trxn_id to payments made by a bulk payment
* Don't specify payment_instrument_id for buildPaymentForm
* Catch error if user tries to add a payment but uses a string instead of a contribution ID
* For master payment invoice - Display line items from all connected payments

## Release 0.9

#### Collect Payments

* Enable enter key to "Add payment".
* Fix issues with bulk identifer not always correctly added/removed.
* Enable sweetalert for nicer error messages.

## Release 0.8

* Fix PHP notice when tax/fee amount is not set
* When deleting master bulk payment clear the identifier field for all linked contributions
* If payment ID does not exist report back to user instead of an error

## Release 0.7

* Make amounts clearer on payment forms
* Switch to using custom field to hold bulk identifier
* Fix for processBillingAddress creating duplicate contacts for bulk payments
* Add support for 'removing' contributions from the bulk contribution if they are paid individually

## Release 0.5

* Remove 'Done' button from invoice
* Remove 'Hello' from Collect payments

## Release 0.4

* Change how payment amount is displayed on invoice
* Fix issue with retrieving master bulk identifier
* Add an invoice reference to the bulkinvoice form
* Allow passing the bulk identifier instead of contribution ID to the bulk invoice form
* Support different formats for displaying names of individual contributions
* Change 'Showing 1 of 2 of 2 entries' to 'Number of payments selected' and subtract one because last row is totals
* Disable enter key on CollectPayments form
* On CollectPayments label the 'Add contribution ID' field as 'Add Payment Number'
* On CollectPayments label the submit button 'Next'
* Only add pending contributions to a bulk contribution
* Fix collect payments settings page not loading

## Release 0.3

Initial public release
