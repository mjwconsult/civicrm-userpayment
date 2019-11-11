# userpayment

![Screenshot](/images/screenshot.png)

This extension adds various forms for making payments in CiviCRM.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.1+
* CiviCRM 5.13

## Installation

See: https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension

## Usage

### General Settings

Configure via *Administer->CiviContribute->User Payment Forms->General Settings*

* Select the financial type to use for bulk payments (default is Bulk Payment). This can be used later to filter these payments out of reports etc.
* Select the format for display contact names on bulk contribution lists (ie. display name or initials).

### Make Payment Form
This allows a user to make a payment on an existing contribution if they have permission and the correct URL.

* Configure via *Administer->CiviContribute->User Payment Forms->Make Payment*
* Example URL: http://localhost:8000/civicrm/user/payment/add?coid=583&cid=202&reset=1
  * coid = Contribution ID
  * cid = Contact ID
  * cs = Checksum (Optional if you don't want the user to need to login first).

### Collect Payment Form

This form allows you to select / add contributions by their ID. They will be linked to a "master" contribution which 
can be used to make payment via the *Bulk Payment Form* or the *Bulk Invoice Form*

* Configure via *Administer->CiviContribute->User Payment Forms->Collect Payments*
* Example URL: http://localhost:8000/civicrm/user/payment/collect?cid=202&id=bulk1&reset=1
  * cid = Contact ID
  * id = the (unique) identifier for the collection of bulk payments (could be a timestamp for example).
  * cs = Checksum (Optional if you don't want the user to need to login first).

On submit it redirects to a URL which defaults to the bulk payment form. coid and cid parameters are automatically appended to the URL.

### Bulk Payment Form
This allows a user to make a payment for a collection of payments.
(actually a contribution linked to a set of other contributions via check_number field)
on an existing contribution if they have permission and the correct URL.

* Configure via *Administer->CiviContribute->User Payment Forms->Bulk Payment*
* Example URL: http://localhost:8000/civicrm/user/payment/bulk?coid=583&cid=202&reset=1
  * coid = Contribution ID
  * cid = Contact ID
  * cs = Checksum (Optional if you don't want the user to need to login first).
  
If a contribution linked to a bulk contribution is paid individually it will be removed from the bulk contribution.

### Bulk Invoice Form

This displays an "invoice" on screen which the user can save/print and use later to manually update the individual contributions.

* Configure via *Administer->CiviContribute->User Payment Forms->Bulk Payment Invoice*
* Example URL: http://localhost:8000/civicrm/user/payment/bulkinvoice?coid=583&cid=202&reset=1
  * Pass one of coid **OR** id, not both!
  * coid = Contribution ID
  * id = the (unique) identifier for the collection of bulk payments (could be a timestamp for example).
  * cid = Contact ID
  * cs = Checksum (Optional if you don't want the user to need to login first).

