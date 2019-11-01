## Information

Releases use the following numbering system:
**{major}.{minor}.{incremental}**

Where:
* major: Major refactoring or rewrite - make sure you read and test very carefully!
* minor: Breaking change in some circumstances, or a new feature. Read carefully and make sure you understand the impact of the change.
* incremental: A "safe" change / improvement. Should *always* be safe to upgrade.

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
