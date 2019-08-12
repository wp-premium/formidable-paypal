== Changelog ==
= 3.06.01 =
* New: Add Paypal triggers to API action.
* Fix: Show update notice for administrators only.
* Fix: Add missing close select tag in global settings.

= 3.01 =
* Make sure email settings are trimmed before comparing to the email sent from PayPal
* Allow amounts with . for thousands and , for decimal. If a . is used in the decimal place for the amount when the currency uses a , make it work anyway

= 3.0 =
* Add support for recurring subscriptions
* Include settings to make changes to a form field when a payment is received
* Add rows to the CSV export and entry listing tables to include payments and payment status
* Sort the Payments page by form
* Add event options to Emails, MailChimp, and Twilio actions so they can be triggered when a payment is complete or fails
* Save the log with WP file methods and fallback to posting in the php error log
* Switch to getting updates from FormidablePros.com
* Allow the email, return url, cancel url, and currency to be changed on a per-form basis

= 2.04.04 =
* Allow the amount to be included in the "other" option
* Modify the user-agent in the IPN verification to prevent errors from PayPal

= 2.04.03 =
* Save the IPN parameters with the payment in the format that allows for multiple IPN notifications
* Fix sending users to Paypal when 2.x is installed, but the old settings haven't been moved to a form action
* Increased security related to XSS add_query_arg vulnerability

= 2.04.02 =
* Send the emails when the payment is complete in 2.0+

= 2.04.01 =
* Fix payment listing page when used with Formidable 2.0
* Fix email delay when using with Formidable 2.0
* Allow checkboxes to be selected for the amount

= 2.04 =
* Added Formidable v2.0 compatibility
* REQUIRES at least v1.07.05 of Formidable
* Add translation file

= 2.03.02 =
* Only stop the emails notifications if user will be sent to PayPal for payment
* Don't send to PayPal if saving draft
* Consistently redirect correctly to PayPal after submitting with ajax
* Added Turkish Liras as a payment option
* Renamed "Payments" menus to "Paypal"
* Fixed creating payments for windows servers

= 2.03.01 =
* Make sure email is not sent when payment is complete if the box to hold the email is not checked
* Only send the delayed email if payment is successfully marked complete to prevent duplicate emails
* Removed version fallbacks and add minimum version requirement
* Use conditional logic rows from core plugin

= 2.03 =
* Added option to hold emails until after payment. This will also stop the registration emails.
* Added option to set a custom amount instead of requiring a field to be selected
* Added option for payments to be sent as donations
* Inserted additional logging for easier diagnosing where IPN is failing
* Allow values to be inserted into the item name field using the sidebar options, and removed the dropdown for inserting fields
* Extended conditional logic to more field types
* Switch options to new fields after form is duplicated

= 2.02 =
* Allow payments to be bulk deleted
* Only retrieve PayPal settings when they are used

= 2.01.01 =
* Remove more globals
* fix IPN notification

= 2.01 =
* Send info to PayPal after formatting the url values for languages
* Update the automatic update code
* Updates for strict standards
* Remove globals and defines
