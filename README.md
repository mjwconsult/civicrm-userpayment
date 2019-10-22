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

### Make Payment Form
This allows a user to make a payment on an existing contribution if they have permission and the correct URL.

* Configure via *Administer->CiviContribute->User Payment Forms->Make Payment
* Example URL: http://localhost:8000/civicrm/user/payment/add?coid=583&cid=202&reset=1
  * coid = Contribution ID
  * cid = Contact ID
  * cs = Checksum (Optional if you don't want the user to need to login first).

## Known Issues

(* FIXME *)
