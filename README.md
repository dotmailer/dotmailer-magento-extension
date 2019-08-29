Engagement Cloud for Magento
==========================================

## Description

This extension integrates Engagement Cloud with Magento Open Source 1 (Community Edition) and Magento Commerce 1 (Enterprise Edition).

- [Full support documentation and setup guides](https://support.dotdigital.com/hc/en-gb/sections/360000722920-Engagement-Cloud-for-Magento)

## Compatibility

- Magento >= 1.6.2

## Installation

This module should be installed using [modman](https://github.com/colinmollenhour/modman#installation). 

Once `modman` is installed and available, run the following in your Magento root:

```
modman init
modman clone https://github.com/dotmailer/dotmailer-magento-extension.git
```

Don't forget to enable symlinks in:
`System->Configuration->Advanced->Developer->Template Settings`

Note: Following patch SUPEE-9767 (https://magento.com/security/patches/supee-9767) enabling symlinks via the admin panel is no longer supported. This can be done by running the following SQL query:

```
INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`)
	VALUES ('default', '0', 'dev/template/allow_symlink', '1')
	ON DUPLICATE KEY UPDATE `value` = '1'
```

## Contribution

You are welcome to contribute to Engagement Cloud for Magento! You can either:
- Report a bug: create a [GitHub issue](https://github.com/dotmailer/dotmailer-magento-extension/issues/new) including description, repro steps, Magento and extension version numbers
- Fix a bug: please fork this repo and submit the Pull Request to our [Develop branch](https://github.com/dotmailer/dotmailer-magento-extension/tree/develop)
Request a feature on our [roadmap](https://roadmap.dotdigital.com)

# 6.4.19

###### Bug Fixes
- We now pull the reward points balance from the correct table, so an accurate balance is shown in Engagement Cloud.

# 6.4.18

###### Improvements
- We've audited the extension with a view to improving overall speed and efficiency. Particular attention has been paid to reducing database impact, pruning observer functions and cleaning up legacy code.
- Columns for segment ID data (used by merchants running Magento Commerce 1), are now added via the regular schema install script, and populated in batches to minimise performance impact.
- We've strengthened security by removing code that disabled SSL certificate verification. If this is required in some installations, it can be overridden by a config key.

###### Bug Fixes
- We've updated our check for an active coupon code in the Rule collection, to prevent a false positive result which was causing additional queries to be run on every page view.
- All extension logging is now routed via our dedicated connector log file.
- New customers, registering at checkout, will now be enrolled onto new customer automations, if configured.

# V6.4.17

###### Improvements
- Simple products in catalog sync and external dynamic content will now link to their parent (configurable, grouped or bundled) product pages if they are not visible individually. 
- Upon installing our module, both Page Tracking and ROI Tracking will be enabled by default.

###### Bug Fixes
- We’ve fixed a bug that prevented modified products being synced to all stores to which they belong.
- We’ve fixed a bug relating to our removal of the dependency on the Magento core review module. 
- Order confirmation emails will now be sent via the correct transactional email account, if configured to do so at website or store level.

# V6.4.16

###### Improvements
- We've improved the coverage of catalog sync by allowing selected custom attributes to be included in the synced data.
- In catalog sync, we are now syncing scoped (store and website) values for products, instead of only the default-level values.
- We resolved some code duplication in the dynamic content blocks.

###### Bug Fixes
- We are now cleaning any custom transactional data keys prior to import, removing invalid (non-alphanumeric) characters, but not skipping records as before.
- The Magento core review module is now decoupled from our contact sync.

# V6.4.15

###### Improvements
- We've improved the handling of double opt-in statuses in customer and subscriber syncs.
- We've made the re-subscription process more robust, ensuring that as statuses change in Magento and Engagement Cloud, subscribers are not accidentally unsubscribed.
- We've moved our announcement feed checking tool into a cron script for efficiency.
- To improve on boarding for larger stores, we've decoupled our installation routine. Our SQL tables can now be populated via a configurable command line script, after the module has been activated.
- We've removed any dependency on Magento's Mage_Review module if review syncing is not active.
- We now automatically mark as sent any campaigns that Engagement Cloud indicates are still "Processing" after 2 hours, in order to ensure subsequent campaigns are sent.  
- We've made various performance enhancements by refining our usage of some observers.

###### Bug Fixes
- We've fixed an issue with incorrect scope when saving the Engagement Cloud API endpoint URL. [External contribution](https://github.com/dotmailer/dotmailer-magento-extension/pull/312)
- We've repaired automation enrollments for guest subscribers when double opt-in is enabled in Magento.
- We've made the menu item for Abandoned Carts visible to all users with access to Engagement Cloud configuration.

# V6.4.14   

###### Bug Fixes
- We've fixed a problem with scheduled campaign sends, arising from campaigns stuck in a "Processing" state on Engagement Cloud. In such cases, we will expire campaigns that have been "Processing" for longer than two hours.

# V6.4.13

###### Bug Fixes
- We've fixed a bug that caused the catalog sync to skip products if invalid data keys were supplied.
- We've fixed a bug related to the use of `utf8_decode` in email templates (note this discontinues support for emojis in email subject lines).
- We've fixed a minor error thrown during the Engagement Cloud template sync. 

# V6.4.12

###### Improvements
- We've improved the performance of the product sync process by setting a limit when querying products before import
- Resubscribed contacts weren't added back to the "subscriber" address book; they are now
- Configurable, grouped, and bundled products are now correctly synced to Engagement Cloud with the lowest relevant price of their children

###### Bug Fixes
- We've fixed incorrect syntax in the Order model (#309)
- We've fixed an issue that caused errors within the Sweet Tooth (Smile.io) integration

# V6.4.11

###### Improvements
- Users can now get SEO-friendly product URLs when syncing their catalog to Engagement Cloud

###### Bug fixes
- Cancelled email sends no longer prevent new sends from going out successfully
- We've optimised database queries, meaning that users with massive databases no longer encounter slow best seller external dynamic content load times
- An exception is no longer thrown when getting rating table name

# V6.4.10

###### Improvements
- 'dotmailer' has been renamed to 'dotdigital Engagement Cloud' (see why [here](https://blog.dotdigital.com/the-story-behind-dotdigital/))
- Email addresses are now validated against the syntax of RFC 822 (FILTER_VALIDATE_EMAIL) by the guest abandoned cart process

# V6.4.9
##### Bug fixes
- Guest subscribers were being synced with Website_Name instead of Store_view name ; this has now been corrected.
- We've fixed an issue whereby the automation cron would attempt to enrol on the wrong Engagement Cloud account in a multi-website context
- We've fixed an issue related to accessing the email_review table when the instance database is using a prefix

# V6.4.8

###### Bug fixes
- Transactional emails weren't being sent using Magento's queuing system (and Cronjob) introduced in Magento EE 1.14.1 and Magento CE 1.9.1; they are now.
- Saving Engagement Cloud email template settings with no changes would reset any previously configured email to the default templates; this has now been fixed.
- We've fixed an issue which caused products to have incorrect URLs when the catalog was synced at store level
- We've fixed an issue which stopped the product image ALT tag from being properly set in the abandoned cart's external dynamics content.
- We are now properly disabling the deprecated CURLOPT_SAFE_UPLOAD method in PHP 7

# V6.4.7

###### Bug fixes
- Import contacts api call now times out after 600 seconds
- We only add items to the importer queue for enabled websites
- Customers that were created by an admin user in a Magento Enterprise Edition account can now be synchronised to Engagement Cloud

###### Improvements
- In email campaigns, you can now link to either the standard review page or the product page with an anchor of your choice

# V6.4.6

###### Improvements
- We now surface all the first and last purchase categories in customer's sales data fields

# V6.4.5

###### Bug fixes
- We now re-import product catalogs when products have been bulk updated using the 'update attribute' button
- We've fixed a bug introduced in 6.4.4 that generated an importer error in the occurrence of an empty import fault report
- We've refactored code that declared arrays with the short form, instead of the long form
- We've fixed a bug that resulted in malformed data that was not sanitized correctly in all cases

# V6.4.4

###### Security
- We now use a unique time-limited randomly-generated passcode to secure the trial callback URL.
- We've implemented prevention against cross-site scripting in the TrialController.php.

###### Bug fixes
- The importer no longer fails to reset/resend contact import (includes archived folder).
- We've fixed an error that was being caused by the importer.

# V6.4.3
 
###### Security
- On installation, we now auto-generate a unique secret key that is used to access external dynamic content and trial signup callbacks.

# V6.4.2 

###### Features
- You’re now able to record your customers and guests’ consent and store it using Engagement Cloud’s new ConsentInsight.

###### Improvements
- We've optimised the way our install script imports data into the extension tables, so it now imports small batches rather than all in one go.


# V6.4.1

###### Features
- Users can now import only those Magento contacts who've opted-in (customer subscribers, guest subscribers, and other subscribers).
- Users now get warned when they're about to sync non-subscribers into their Engagement Cloud account. 

###### Improvements
- We've added a new option in 'Configuration' > 'Abandoned Carts' that allows to send abandoned cart emails to subscribed contacts only. On fresh installation contacts who haven't opted in will no longer be included.
- We've added a new option in 'Automation' > 'Review Settings' that allows to send review reminder emails to subscribed contacts only. On fresh installation contacts who haven't opted in will no longer be included.

###### Bug fixes
- We've fixed the catalog sync so it now syncs all products across all created collections when it's configured to sync on store level. 

# V6.4.0

###### Features
- Transactional email templates: You're now able to create, edit, translate and test Magento transactional emails in Engagement Cloud and map them at default, website or store level.

###### Improvements
- We now import new subscribers with the correct opt-in type (single or double) depending upon Magento's "Need to confirm" setting

###### Bug fixes
- Deleted contacts in Magento weren't being correctly marked as imported when removed from Engagement Cloud;they are now
- An error could occur while creating a trial account in the US and APAC regions; this is now fixed
- We've fixed an issue which caused an error when displaying the wishlist dynamics content on PHP 5.4
- Some products with individual visibilities were getting ignored by the importer; this has been fixed.

# V6.3.9

###### Improvements:
- We've improved the password encryption using Magento's encryption framework.
- Transactional email settings can now be set at the store level.

###### Bug fixes:
- We've fixed the process for abandoned carts when the first one is disabled.
- We used to create duplicated contacts when email addresses did not match with the same case; this no longer happens.
- We've fixed an error related to importing orders having both virtual and physical products.
- In the case where Magento's double opt-in setting ('Need to confirm') was enabled, we used to import subscribers before they confirmed; this is now fixed.
- We've fixed a typo regarding the number of days in the trial sign up banner.

# V6.3.8

###### Improvements
- We've improved the way we update contacts before sending abandoned cart emails in a multi website context so that they no longer risk to recieve a wrong cart content from a different website.

###### Bug fixes
- We've changed the validation of new subscribers for automation so that they no longer get enrolled multiple times into the new subscriber program.
- Transactional emails can now be set up at website level.
- We've fixed an order Insight data issue related to the data type for the following fields - "delivery_address" and "billing_address".
- In the case where Magento's double opt-in setting ("Need to confirm") was enabled, we used to import subscribers before they confirmed; this is now fixed.
- We've added a check to ensure that the first abandoned cart email is mapped before doing the send.

# V6.3.7

###### Bug fixes
- We’ve fixed duplicates for new subscriber automation.
- We’ve fixed the process for abandoned carts when the first one is disabled.

# V6.3.6

###### Bug fixes
- Subscriber sales data fields no longer get incorrectly synced when multiple store views exist under a single website.
- We’ve introduced new validation when deleting cron job CSV files.
- Page tracking data wasn’t getting sent for North America (region 2) or Asia Pacific (region 3) accounts using the connector; this has been resolved.
- An expiry days value of ‘0’ in the external dynamic content coupon code URL would set the coupon code’s expiration date and time to the coupon code’s creation date and time; this has been fixed.

# V6.3.5 Release Notes

###### Improvements
- We've introduced a new Abandoned cart report table and improved the way we process and send abandoned cart campaigns.

###### Bug fixes
- Customer sales data fields could get mixed up when multiple store views existed under a single website; this has been fixed.
- An error would occur due to the attempted retrieval of a non-object in the newsletter subscription section; this no longer happens.
- Email activity for new customers in the admin panel has now been fixed.
- We fixed an error that would occur when trying to send campaigns with a disabled API connection.

# V6.3.4 Release Notes
- Customers who registered and then went on to subscribe were getting created twice; this no longer happens.
- For the most purchased custom brand attribute, we've added support for multistore values.

# V6.3.3 Release Notes
###### Improvements
- Syncing sales data for guest subscribers is now optional and is managed with a new setting in 'Engagement Cloud' > 'Developer' > 'Import settings'.
- Corrupted and missing payment methods no longer stop the importer from running.
###### Bug fixes
- Removing subscribers now correctly removes them from both the contact table and subscriber address book.
- The duplication of a created contact for a unique guest subscriber is now fixed and no longer happens.
- We've fixed a problem with additional attribute data being included for transactional data related to orders and quotes.
- SMTP logging and configuration path has been fixed.

# V6.3.2 Release Notes
 - We’ve fixed a problem in which store views didn’t exist.
 - Bulk order sync now has a delay (of 60 mins) before being imported.
 - Trial account enhancements that we’ve already implemented in our Magento 2 connector are now also implemented for Magento 1.
 - We’ve fixed the collection filter for flat products.
 - External dynamic content wasn’t aligning centrally on mobile devices; it does now.
 - Changes in 'Subscriber' status weren't being sent back to Magento from Engagement Cloud; this has been fixed.
 - We’ve implemented an IP address change.
 - Before creating a contact, an automatic check is made to ensure the API is enabled.
 - The class Zend_DB_Expr is now used for columns with expressions.
 - We’ve fixed a problem relating to the website ID that’s used when getting a store’s configuration in the helper.

# V6.3.1
###### Bug fixes
 - Unsubscribers from Magento weren’t being removed successfully from the subscriber address book; they are again now(#233).
 - Contact datafields weren't getting updated in Engagement Cloud for existing customers in Magento whose data changed; this has been fixed.(#244)
  
# V6.3.0
###### Features
- Code audit 2017.
- Improve the order sync by delaying the import. 
- Added R3 IP.

###### Bug fixes
- Subscribers with datafields fixes.
- Email change detection to update the same contact.
- Campaign bulk sync proccessing ids.
- Quote validation for billing/shipping address.
- Increased cURL timeout for TD imports.
- Improve install script running time for updating subscribers.
- Transactional emails only available on global level.
- Revert the finding guest feature.

# V6.2.5
###### Features
 - Sync sales data fields for guest subscribers
###### Bug fixes
 - Orders import with custom options fix.
 - Remove Campaign "is_sent" column what is not used anymore.
 - Set the correct customer store for "go to cart" link.
 - Customer stats email activity.
 - Fix the sale coupon rules exception.
 - Guest finding and grouping.
 - Send email for new account compatibility with > 1.9.3.0.
 - Store name populated wrong when using automation for new subscribers.
 - Update product visibility for the EDC content.
  
# V6.2.4
###### Bug fixes
 - EDC table content check fix.

# V6.2.3
###### Bug fixes
 - Double coupon codes generated.
 - Strict data type for orders.

# V6.2.1
###### Improvements
 - Removed Raygun integration.

# V6.2.0
###### Features
 - Coupon EDC expiration date. You can set the expiration date for coupon included into the URL
 - Improve finding guests. Guest will be added in bulk to the table.
 - Add new automation for first customer order event.
 - EDC include all product types to have an image and inlcude the price range available for the product.   

###### Bug fixes
 - EDC fixed the prefix for table names.
 - Fix unsubscribeEmail register already exists.
 - New installation do not get the customers mark as subscribers.
 - Automation program enrollment without unserialized is failing.
 - Exclution Rules conditional mapping fix.   

###### Improvements
 - Appcues script will run in admin on connector pages only.
 - Improve the index for the email campaing table.
 - Allow to include Order multiselect attributes. 

# V6.0.0
## Features
 - New improved Importer.
 - Express account creation.
 - Api support region.
 - Magento partner programme.
## Bug fixes
 - Transactional data disabled by Transactional Allowance.
 - Abandoned carts template.
 - Api username obscure validation.
 - Update security on get basket content.
 - Saving a review in admin.
 
# V5.3.0
## Features
 - Support for Engagement Cloud regions
## Bug fixes
 - Bug fixed for fatal error on manual sync
## Improvements
 - Magento code audit changes x3
 - Email validation on ajax call

# V5.2.0
## Features
 - Run Importer Button.
 - Cron timings for diffrent settings to run cronjobs.
## Bug fixes
 - Rule condition for abandoned carts.
## Improvments
 - Refactor EDC pages.
 - New "Suppressed by you" into suppresion list.
 - New suppressed contacts sync.
 - More Code Audit changes.

# V5.1.0
## Bug fixes
 - Audit changes A1 - A14
 - Skip website if no store assigned
 - Manufacturer attribute
 - Raygun - change the title message to non unique
 - Exclusion rules ajax call protocol fix
## Features
 - Abandoned Product Name
 - Importer API Enchased
 - Add indexes to tables
 - Transactional email merged to core
 - Order status option source changed
 - System log viewer
 - Single deletes for importer

# V5.0.4
## Bug fixes
 - Increased the cron sync times to 15 minutes.

# V5.0.3
## Bug fixes
 - Magento code audit. Brins a lot of performance and architectural imrovements.
 - Fixed fatal error on quote single sync
 - Fixed oAuth redirect
 - Fixed oAuth disconnect button
 - Order sync report column name typo fix
 - Removed hidden form fields connector_customer_id and connector_customer_email from customer accouunt additional newsletter management
 - Fixed rewrite sendNewAccountEmail function to call parent function with actual params instead of default
 - Fixed EDC blocks to load order from registry that was saved in registry from controller. If not found will throws Exception from now.
 - Fixed emmail capture fails on one of the email fields if both newsletter and billing email are presented on the same page.
 - Fixed voucher styling bug where no style was being loaded from config.
 - Fixed contact fields data calculation not working because of status not being an array. Added check if it is an array before start working on it.
 - Fixed Page/ROI data Enable config path in xml.
 - Fixed callback action fro oAuth. Now we check if returned state is an actual admin that exist in Magento.
 - Fixed duplicate review entries. Only approved review will saved for sync.
 - Fixed contact sync. Check if manufacturer attribute exist before calling for it otherwise Magento throws an error and sync fails.

# V5.0.2
## Bug fixes
 - Fixed fatal error while doing single sync. Key does not exist/not an object.
 - Fixed rewrite of sendNewAccountEmail function to call parent without default values.
 - Fixed OAUTH disconnect link
 - Contact sync check if manufacturer attribute exist before pulling value. 

# V5.0.1
## Bug fixes
 - Fixed upgrade script so it does not get skipped
 - Added ACL to required controllers

# V5.0.0
## Features
 -  New Data Importer
 -  Abandoned cart exclusion rules
 -  Review request exclusion rules
 -  Include product attributes in order sync
 -  Include product custom options in order sync
 -  Update transactional data for modified orders
 -  Configure order status used in customer calculations
 -  Automation Queue Enrolment
 -  Easy Email capture on newsletter signup
 -  Campaign stats for customers in admin
 -  Seperate customer and guest syncs
 -  Add to cart button for abandoned carts
 -  Coupon code styling
 -  Editable "view now" text for EDC pages
 -  Nosto fallback products
 -  Queued customer deletion
 -  Log long API response
 -  Catalog importer
 -  Automation enrollment based on order status
 -  Reset Tables button
 -  IP restrictions on EDC pages
## Bug fixes
 -  Reduced default batch size and more frequent batching
 -  Custom order attributes not syncing
 -  Performance improvements to all data syncs
 -  Performance improvments to data analysis page
 -  Admin place an order, wrong enviroment
 -  Update RFM table
 -  Observers that are hit more then once
 -  Refactor of the quote sync
 -  For sync/observer events only if the feature enabled
 -  Sync quotes only with products in it
 -  Canceled orders removed the trans data
 -  ACL for enterprise report tables. Access denied
 -  Date localization for bestsellers and mostviewed
 -  Resubscribe subscriber with new status
 -  Remove the API log table
 -  Add indexes to tables
 -  Automation enrollment per website level
 -  Fix for rec's per item logic for EDC pages

### V4.0.0
## Features
 - Transactional email.
 - Dashboard display conflict checker.
 - Namespace updated to Ddg Automaiton.
 - Enterprise version combined.
 - Appcues onboarding.
 - Raygun control to disable and enable.
 - Sweetooth refferal link.
 - Custom OAUTH domain/redirect link options.
 - Use Engagement Cloud template for transactional emails.
 - Wishlist EDC with related, upsell and crosssell.
 - Customer trend data.

## Bug fixes
 - Sync limits lowered to 5000 contacts and 200 orders.
 - Review is submited before it's approved.
 - Abandoned Carts triggered from the "LAST_QUOTE_ID".

# V3.3.0
## Features
- Nosto Integration.
- Easy Email Capture(trademark).
- Reviews.
- Quote recommendations.
- Wishlists.
- Disable Newsletter Success.
- Disable Customer Success.
## Bug fixed
- Cleaning phpspecs standards.
- Checkout awareness for abandoned carts.
- Compatibility with the older versions 1.6.2(full compatibility).
- Automation Studio fix width and suppress footer.
- Bestsellers will select and filter the sealable items in collection.
- Not enough coupons generated.

# V3.2.0
- Transactional Emails Refactor.
- System Status Dashboard
## Features
- System Status Dashboard.
- RFM Analisys.
- Api Status.
- Raygun Integration.
- Feed for new releases.
- Number of days to delete orders from order created date.
- Abandoned cart limit.
- Log for all API calls.
- Alternative abandoned baskets.
- Mailcheck integration
## Bug fixed
- Magor Refactoring.
- Cover all the code with testing.
- Automation when subscriber is not imported yet.
- Security improvment for api credentials.
- Campaigns for multi website.
- Mailcheck frontend notice.
- Improve reset subscribers.
- GeoIp redirection for the dynamic content.
- Config table to store related data not to update on every request.
- Improve code for dynamic content.
- Transactional emails refactoring.
- Payment method for orders.
- Check for feature active for disabled accounts.
- Disable sync for not mapped addressbooks.
- App emaulation to match the env the order.
- Subdomain dynamic urls.
- Buttons to run sync services.
- Ajax reset of the contacts for an updated addressbooks.


# V3.1.0

## Automation

- Automation Enrolment.

## Features

- Sweet Tooth Integration.
- Transactional Emails.
- Auto create data fields needs total refund amount added.
- Add 240 hours onto lost basket 3 both guest and customer.

## Bugs fixed
 
- Dynamic URLS with no value.
- Transactional data missing product data.
- Orders over 1000 showing 1.
- Automap on website level.



# V3.0.3

## Single customer sync.

* Single Customer Sync Button
* ROI fix
* Code standards refactoring

# V3.0.2

## Automation Studio.

* OAUTH & Menu
* Map product attribute-set to transactional data
* Tracking code SSL fix


# V3.0.1

## Transactional Emails Post Release Update.

* Translations update
* Default values update
* Transactional emails enabled fix
* Manully map the customer ID field
* Emails Reports enchase with website id field

# V3.0.0

## Transactional Emails.

* New Order
* New Order for Guest
* Order Update
* Order Update for Guest
* New Invoice
* New Invoice for Guest
* Invoice Update
* Invoice Update for Guest
* New Credit Memo
* New Credit Memo Guest
* Credit Memo Update
* Credit Memo Update for Guest
* New Shipment
* New Shipment for Guest
* Shipment Update
* Shipment Update for Guest
* Customer
* New Customer Account 

## Features 

* Newsletter Dashboard.
* Newsletter Contact.
* Newsletter Orders.
* Newsletter Campaigns.
* Product Recomendations styling.
* TE styling.
* Subscribers reset button.
* Custom datafields.
* New Lost Baskets.


## Fixes

* New SMS Fixes.
* Sync Orders by store.
* Subscribers multiwebsite fix.
* Suppressed contacts for orders.
* Wishlist contact id update fix.
* Suppressed contacts per website.
* Dynamic content for multi-site (multi-currency).
* Transactional data key.
* Reset orders direct query.
* Naming convention for connector compatibility.
# V.2.0.5 
* Use the Order statuses to send the SMS.
# V.2.0.4 
* Reset subscriber_imported for reimport.
* Suppressed contacts button in admin settings.
* Cleaned the Engagement Cloud from db.
* Fix for table prefix names.
* Duplicate email address.
* Website id table update for null values.
* Ignore deleted sales orders from email_order table.
* Dropping number of subscribers from address book.
# V.2.0.3 
* Delete transactional data using email address.
* Subscriber Sync - fix the contact id when to unsubscribe.
* Subscriber Sync Empty Request.
* Change the time format for filenames.
