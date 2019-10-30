<?php

use CRM_Userpayment_ExtensionUtil as E;

return [
  'userpayment_bulkfinancialtype' => [
    'name' => 'userpayment_bulkfinancialtype',
    'type' => 'Integer',
    'quick_form_type' => 'Select',
    'default' => 10,
    'add' => '5.13',
    'title' => ts('Financial Type for bulk payments'),
    'html_type' => 'select',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Specify the financial type for the bulk payments'),
    'pseudoconstant' => ['callback' => 'CRM_Contribute_PseudoConstant::financialType'],
    'settings_pages' => ['userpayment_general' => ['weight' => 1]],
  ],
  'userpayment_paymentadd_title' => [
    'name' => 'userpayment_paymentadd_title',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => E::ts('Make Payment'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Page Title'),
    'description' => E::ts('Default page title for the add payment page'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 0,
      ]
    ],
  ],
  'userpayment_paymentadd_introduction' => [
    'name' => 'userpayment_paymentadd_introduction',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => E::ts('Please enter your details and make a payment'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Introduction'),
    'description' => E::ts('Introduction text for the page'),
    'html_attributes' => ['cols' => 60, 'rows' => 5, 'class' => 'crm-form-wysiwyg'],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 5,
      ]
    ],
  ],
  'userpayment_paymentadd_emailreceipt' => [
    'name' => 'userpayment_paymentadd_emailreceipt',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Send an email receipt?'),
    'description' => E::ts('Send a receipt on payment?'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 10,
      ]
    ],
  ],
  'userpayment_paymentadd_freezeamount' => [
    'name' => 'userpayment_paymentadd_freezeamount',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Freeze the amount to pay?'),
    'description' => E::ts('Do you want to allow the user to modify the amount they are going to pay - they could choose to pay more or less than the required amount.'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 12,
      ]
    ],
  ],
  'userpayment_paymentadd_captcha' => [
    'name' => 'userpayment_paymentadd_captcha',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Enable reCaptcha?'),
    'description' => E::ts('Enable reCaptcha on the form?'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 15,
      ]
    ],
  ],
  'userpayment_paymentadd_redirect' => [
    'name' => 'userpayment_paymentadd_redirect',
    'type' => 'Integer',
    'html_type' => 'radio',
    'default' => 0,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('On successful form submission?'),
    'description' => E::ts('What to do on successful form submission'),
    'html_attributes' => [],
    'options' => [
      CRM_Userpayment_Form_Payment::PAYMENT_REDIRECT_THANKYOU => E::ts('Display a thankyou page'),
      CRM_Userpayment_Form_Payment::PAYMENT_REDIRECT_URL => E::ts('Redirect to URL')
    ],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 20,
      ]
    ],
  ],
  'userpayment_paymentadd_redirecturl' => [
    'name' => 'userpayment_paymentadd_redirecturl',
    'type' => 'String',
    'html_type' => 'text',
    'default' => '',
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Redirect URL'),
    'description' => E::ts('The URL to redirect to on successful submission'),
    'html_attributes' => ['size' => 80],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 25,
      ]
    ],
  ],
  'userpayment_paymentadd_redirectthankyou' => [
    'name' => 'userpayment_paymentadd_redirectthankyou',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => 'Thankyou for your payment',
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Thankyou message'),
    'description' => E::ts('The message to display on the thankyou page'),
    'html_attributes' => ['cols' => 60, 'rows' => 5, 'class' => 'crm-form-wysiwyg'],
    'settings_pages' => [
      'userpayment_paymentadd' => [
        'weight' => 30,
      ]
    ],
  ],

  'userpayment_paymentbulk_title' => [
    'name' => 'userpayment_paymentbulk_title',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => E::ts('Make Bulk Payment'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Page Title'),
    'description' => E::ts('Default page title for the bulk payment page'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 0,
      ]
    ],
  ],
  'userpayment_paymentbulk_introduction' => [
    'name' => 'userpayment_paymentbulk_introduction',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => E::ts('Please enter your details and make a payment'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Introduction'),
    'description' => E::ts('Introduction text for the page'),
    'html_attributes' => [
      'cols' => 60,
      'rows' => 5,
      'class' => 'crm-form-wysiwyg'
    ],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 5,
      ]
    ],
  ],
  'userpayment_paymentbulk_emailreceipt' => [
    'name' => 'userpayment_paymentbulk_emailreceipt',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Send an email receipt?'),
    'description' => E::ts('Send a receipt on payment?'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 10,
      ]
    ],
  ],
  'userpayment_paymentbulk_captcha' => [
    'name' => 'userpayment_paymentbulk_captcha',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Enable reCaptcha?'),
    'description' => E::ts('Enable reCaptcha on the form?'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 15,
      ]
    ],
  ],
  'userpayment_paymentbulk_redirect' => [
    'name' => 'userpayment_paymentbulk_redirect',
    'type' => 'Integer',
    'html_type' => 'radio',
    'default' => 0,
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('On successful form submission?'),
    'description' => E::ts('What to do on successful form submission'),
    'html_attributes' => [],
    'options' => [
      CRM_Userpayment_Form_Payment::PAYMENT_REDIRECT_THANKYOU => E::ts('Display a thankyou page'),
      CRM_Userpayment_Form_Payment::PAYMENT_REDIRECT_URL => E::ts('Redirect to URL')
    ],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 20,
      ]
    ],
  ],
  'userpayment_paymentbulk_redirecturl' => [
    'name' => 'userpayment_paymentbulk_redirecturl',
    'type' => 'String',
    'html_type' => 'text',
    'default' => '',
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Redirect URL'),
    'description' => E::ts('The URL to redirect to on successful submission'),
    'html_attributes' => ['size' => 80],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 25,
      ]
    ],
  ],
  'userpayment_paymentbulk_redirectthankyou' => [
    'name' => 'userpayment_paymentbulk_redirectthankyou',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => 'Thankyou for your payment',
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Thankyou message'),
    'description' => E::ts('The message to display on the thankyou page'),
    'html_attributes' => [
      'cols' => 60,
      'rows' => 5,
      'class' => 'crm-form-wysiwyg'
    ],
    'settings_pages' => [
      'userpayment_paymentbulk' => [
        'weight' => 30,
      ]
    ],
  ],

  'userpayment_paymentbulkinvoice_title' => [
    'name' => 'userpayment_paymentbulkinvoice_title',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => E::ts('Bulk Payment Invoice'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Page Title'),
    'description' => E::ts('Default page title for the bulk invoice page'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentbulkinvoice' => [
        'weight' => 0,
      ]
    ],
  ],
  'userpayment_paymentbulkinvoice_introduction' => [
    'name' => 'userpayment_paymentbulkinvoice_introduction',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => E::ts('Please save this page and manually record payment against each of the contribution IDs'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Introduction'),
    'description' => E::ts('Introduction text for the page'),
    'html_attributes' => [
      'cols' => 60,
      'rows' => 5,
      'class' => 'crm-form-wysiwyg'
    ],
    'settings_pages' => [
      'userpayment_paymentbulkinvoice' => [
        'weight' => 5,
      ]
    ],
  ],

  'userpayment_paymentcollect_title' => [
    'name' => 'userpayment_paymentcollect_title',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => E::ts('Collect Payments'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Page Title'),
    'description' => E::ts('Default page title for the collet payments page'),
    'html_attributes' => [],
    'settings_pages' => [
      'userpayment_paymentcollect' => [
        'weight' => 0,
      ]
    ],
  ],
  'userpayment_paymentcollect_introduction' => [
    'name' => 'userpayment_paymentcollect_introduction',
    'type' => 'wysiwyg',
    'html_type' => 'textarea',
    'default' => E::ts('Please add the contribution IDs that you want to pay for'),
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Introduction'),
    'description' => E::ts('Introduction text for the page'),
    'html_attributes' => [
      'cols' => 60,
      'rows' => 5,
      'class' => 'crm-form-wysiwyg'
    ],
    'settings_pages' => [
      'userpayment_paymentcollect' => [
        'weight' => 5,
      ]
    ],
  ],
  'userpayment_paymentcollect_redirecturl' => [
    'name' => 'userpayment_paymentcollect_redirecturl',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'civicrm/user/payment/bulk',
    'add' => '5.13',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Redirect URL'),
    'description' => E::ts('The URL to redirect to on successful submission - default (civicrm/user/payment/bulk), contributionID (coid), + contactID (cid) will be appended as parameters'),
    'html_attributes' => ['size' => 80],
    'settings_pages' => [
      'userpayment_paymentcollect' => [
        'weight' => 25,
      ]
    ],
  ],
];
