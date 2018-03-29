2.0.0 Alpha 2 / 2018-03-29
==================

  * Add multi-user support (#195)
  * Update Dependencies (#202, #199)
  * Add button to print quotes and invoices (#193)
  * Display literal country name (#197)
  * Fix RequireJS not being inlcluded anymore
  * Add server_version to doctrine/dbal configuration

2.0.0 Alpha 1 / 2017-08-22
=========================

  * Rename CSBill to SolidInvoice
  * Save users on invoices and quotes in a linking table (#184)
  * Use constant when displaying application name (#186)
  * Set proper required PHP version in AppRequirements (#185)
  * Replace FOSRestBundle with api-platform (#178)
  * Add support for monetary discount values (#182)
  * Update Vat rates (#181)
  * Revamp UI (#179)
  * Update JS loading (#177)
  * Update mailer to use env values (#174)
  * Replace Encryption class with defuse/encryption library (#175)
  * Refactor the system settings and config (#173)
  * Update Marionette to V3 (#170)
  * Update form handlers to use proper options (#172)
  * Add invoice and quote cloner (#171)
  * Replace finite state machine with Symfony workflow (#166)
  * Replace controllers with actions (#165)
  * Update all files to PHP 7 strict types (#163)
  * Update config to a standardized format (#162)
  * Update Twig to 2.0 (#160)
  * Move bundles one folder up (#159)
  * Update dependencies to tha latest versions (#154)
  * Update FOSUserBundle to 2 (#153)
  * Upgrade PHPUnit to the latest version (#149)

1.1.0 / 2017-02-15
==================

  * Add multi-currency support (#148)
  * Fix JS translations not setting global Translator variable anymore (#126)

1.0.2 / 2016-10-15
==================

  * Allow to set custom config path (#125)
  * Fix additional details display on contact card (#124)
  * Fix address not pulling through to Google maps (#123)
  * Disable running of cron if application is not installed (#122)
  * Update travis config to not install composer dependencies from source

1.0.1 / 2016-09-21
==================

  * Remove references to primary details on contact (#119)
  * Fix incorrect usage of invoice form type (#120)
  * Add migrations to set factory for payment methods (#121)

1.0.0 / 2016-09-20
==================

  * Move email from contact_details to contact table (#117)

0.8.1 / 2016-08-10
==================

  * Remove index rename in 0.8 migrations

0.8.0 / 2016-08-08
==================

  * Format payments as money (#116)
  * Format discount properly in grid (#115)
  * Edit and Delete addresses from client view (#114)
  * Fix contacts not deleting when updating a client (#113)
  * Update Tests (#111)
  * Don't display pay button if invoice is already paid (#112)
  * Update payments to use dynamic gateways
  * Add Symfony 3 compatibility
  * Skip user creation if user already exists during installation
  * Fix requirements check exiting when installing through the command line
  * Fix invalid locale and currency when passed through command line flags
  * Fixed repository fetch
  * Update other dependencies
  * Upgrade Symfony to V2.8
  * Upgrade Doctrine to V2.5

0.7.0 / 2016-06-20
==================

  * Fix datagrid not filtering correctly
  * Added related api content for a client
  * Added get api method for quotes and invoices
  * Allow user to generate a token by sending a username and password to a login url
  * Added token manager, to handle token generation in a more generic place
  * Fix missing property
  * Add api route to get a specific client
  * Change token header to X-API-TOKEN
  * Added custom error pages for json and jsonp exceptions
  * Fix API token create title
  * Show quotes grid on client info page
  * Remove loader for data grid, since it didn't always hide after loading was done
  * Update login page style
  * Fix settings variable overwritten on settings page
  * Update logo in header when a new logo is uploaded
  * Update secret generation to use less bits
  * Fix email settings not showing the proper config when switching between smtp and gmail
  * Remove assetic
  * Added task to watch and compile templates
  * Added gulp tasks for assets
  * Remove PHP 7 from allowed failures in travis
  * Remove salt form user and use auto-generated salt from password_hash
  * Update dependencies and update min PHP version to 5.6
  * Update doctrine/migrations to allow PHP 5.4
  * Fix install command not validating currency and locale correctly
  * Refactor menu into separate bundle
  * Render recurring invoices grid on client view
  * Properly show and hide modals on grid request
  * Added recurring invoices grid
  * Remove APYDataGrid
  * Replaced invoice grid with new grid
  * Added multiple grid handling
  * Fix installation scripts
  * Fix asset url error when no base url is set
  * Update email settings scripts
  * Update logo upload and cron settings
  * Update Symfony to latest 2.7 version
  * Handle fields when editing a quote or invoice
  * Fix label translations and set tax label on invoice and quote tables
  * Calculate totals when saving quotes and invoices
  * Cleanup invoice create and split modules
  * Disable tax if no tax methods is configured
  * Display recurring info
  * Added module for invoice create and client select view
  * Update API token index page
  * Update select2
  * Optimize client view
  * Update client info view
  * Added basic validation using Parsley.js
  * Added currency helper to handlebars
  * Fix routing module and add path helper for handlebars
  * Added handlebars for templates and created generic modal template
  * Added basic credit display to client view
  * Split some default components into separate modules
  * Added lodash library
  * Update jQuery to version 2
  * Replace underscore with lodash
  * Added initial requirejs config
  * Fix create button to not show when user is not logged in

0.6.0 / 2015-12-22
==================

  * Improve Navigation
  * Added Omnipay payment gateways
  * Add extra payment gateway
  * Add option to clone invoices and quotes
  * Don't allow paid invoices to be edited
  * Don't allow the only contact on a client to be deleted
  * Add recurring info to invoice view
  * Added datepicker to recurring invoices
  * [InvoiceBundle] Add support for recurring invoices
  * Added material theme
  * Added base url to installation and to asset configuration
  * Fix logo upoad not displaying
  * Don't add payment button to email if no payment methods are configured
  * Fix notification templates
  * Fix payment methods query when no payment methods is configured
  * Fix money formatter
  * [InstallBundle] Migrate database if version is outdated
  * [InstallBundle] Check if user exists in database when installing application

0.5.0 / 2015-10-14
==================

  * Added REST API
  * Fixed modal backdrop overlay
  * [ApiBundle] Save history of all API calls
  * Add token authentication for API
  * Fix discount showing incorrectly
  * Remove dependency on the intl extension
  * Don't display internal payment methods to client when paying an invoice
  * Update email templates for invoices and quotes
  * Fix #61: Check invoice total instead of balance if invoice is fully paid
  * Added MoneyBundle to implement Fowler's money pattern
  * Added current application version as asset version
  * [CronBundle] Added CronBuundle
  * [InstallBundle] Add cronjob message to cli installer success

0.4.3 / 2015-08-16
==================

  * Added the security:check to travis
  * Update dependencies
  * [InstallBundle] Change secret to 32 bits instead of 64, to avoid algorithm key size error
  * [InstallBundle] Added command line installer
  * Fix client grid credit column
  * Set invoice balance when converting a quote to an invoice
  * Fix error when trying to create a payment

0.4.2 / 2015-07-01
==================

  * [PaymentBundle] Simplify payment method settings to not rely on services to be created
  * Add contact types to database migrations
  * Remove fixtures from installation process
  * Set default email from name and address
  * Move migrations to more version specific files
  * Fix confirm dialog styles
  * Load Router JavaScript earlier to fix Router variable not defined
  * Fix InvoiceManager Unit test

0.4.1 / 2015-06-01
==================

  * Fix incorrect invoice status when creating new invoices
  * Fix config section names for notifications

0.4.0 / 2015-06-01
==================

  * [CoreBundle] Add setting to BCC copy of quote/invoice to specific address
  * [NotificationBundle] Add new Notification bundle
  * Update login page style
  * Added new logo
  * Change client view to use tabs for info
  * [TaxBundle] Extract tax functionality to separate bundle
  * [ClientBundle] [DataGridBunde] Add support for Grid collections, so that you can display multiple grids on the same page
  * [DataGridBundle] Updates to DataGrid
  * Added support for credits
  * [PaymentBundle] Refactored payment process
  * [InstallBundle] Add installation step to create database if it does not yet exist
  * Updated settings
  * Update installation process
  * Updated dashboard
  * Added custom error pages
  * Added Address support for clients
  * Updated minimum required PHP version to 5.4.0

0.3.0 / 2014-09-17
==================

 * Added support for tax rates
 * [InstallBundle] Execute database fixtures directly instead of running in a process
 * [InstallBundle] Execute database migrations directly instead of running in a process
 * [InstallBundle] Fixed installation not working correctly
 * [InstallBundle] Simplified the LICENSE file check
 * Removed JMSDiExtraBundle
 * [CoreBundle] Remove empty test
 * [InvoiceBundle] Fix invoice manager bugs
 * [InvoiceBundle] [QuoteBundle] Added terms and notes to invoices and quotes
 * [CoreBundle] Load app name from settings
 * [InstallBundle] Fix install check when database connection can't connect

0.2.0 / 2014-07-29
==================

 * Added button on dashboard to create new client/quote/invoice
 * Added Dashboard info
 * [PaymentBundle] Added grid to view all payments
 * [PaymentBundle] Add icon to pay now button
 * [QuoteBundle][InvoiceBundle] Add email icon to send buttons
 * [PaymentBundle] Mark offline payments as success by default
 * [PaymentBundle] Add support for dropdown settings in payment settings
 * [PaymentBundle] Updated PaymentRepository with correct methods
 * [ClientBundle] Added list of payments to client view
 * [PaymentBundle] Added completed column to payments
 * [PaymentBundle] Add error to flash message
 * [InvoiceBundle] Add button to pay invoice to email
 * [ClientBundle] Fix client edit
 * [PaymentBundle] Use original password when saving payment method instead of saving it as NULL
 * [PaymentBundle] Set default status to payment
 * [CoreBundle] Add checkbox class to checkbox fields
 * [PaymentBundle] Use select2 for payment method form
 * [PaymentBundle] Only show public payment methods to non-loggedin users
 * [PaymentBundle] Change payment method selection to use select2
 * [PaymentBundle] When marking invoice as paid, add option to capture a payment
 * [QuoteBundle] Fix total column type for Grid
 * [QuoteBundle] Fix Select2 for Quotes
 * [PaymentBundle] Don't show payment options if no payment methods is configure
 * [PaymentBundle] Throw exception when loading invalid invoice
 * [CoreBundle] Render external invoices and quotes with normal header
 * Fix various page titles and add link to client in invoice and quote view
 * [PaymentBundle] Capture payment failure message
 * [InvoiceBundle] Fix payment route for invoice list
 * [PaymentBundle] Change payment capture page to use invoice UUID instead of id
 * [InvoiceBundle] Add payments to invoice view
 * [PaymentBundle] Split payment_details table into two tables
 * [InvoiceBundle] Don't show payment button if invoice is not pending
 * [InvoiceBundle] Add payment icon to invoice index
 * [PaymentBundle] Add enabled option to payment methods
 * [PaymentBundle] Use custom API to get payment settings
 * [PaymentBundle] Added controller for creating payments and dded Paypal Express and offline payment methods
 * [PaymentBundle] Added CRUD for payment methods
 * [PaymentsBundle] Added payments menu item to system menu
 * [CoreBundle] Moved settings to new System menu
 * [PaymentBundle] Added payment bundle

0.1.0 / 2014-06-17
==================

 * [QuoteBundle] [InvoiceBundle] Fix column size when creating a quote/invoice
 * Fix page overflowing
 * [InvoiceBundle] Fix user not added to invoice
 * Changed mode on files
 * Fixed CS
 * Added VersionEye badge to Readme
 * Replace chosen with Select2
 * Update dependencies
 * Clear container file after saving settings to reload new settings
 * [CoreBundle] Fixed discound in twig function
 * [QuoteBundle] [InvoiceBundle] Fix discount on invoices and quotes
 * Added license header to all files. Fixes #5
