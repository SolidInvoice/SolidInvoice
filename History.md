0.4.0 / 2015-05-31
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
