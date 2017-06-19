[![Build Status](https://travis-ci.org/dotmailer/dotmailer-magento-extension.svg?branch=master)](dotmailer/dotmailer-magento-extension)

dotmailer for Magento
==========================================

Full support documentation and setup guides available here - https://support.dotmailer.com/hc/en-gb/categories/202610368-Magento

This module uses modman

First ensure you have modman installed (see here: https://github.com/colinmollenhour/modman#installation)

...and run the following in your magento root:

```
modman init
modman clone https://github.com/dotmailer/dotmailer-magento-extension.git
```

Don't forget to enable symlinks in:
`System->Configuration->Advanced->Developer->Template Settings`

Facts
-----
- community & enterprise version.
- current version - [config.xml](https://github.com/dotmailer/dotmailer-magento-extension/blob/master/code/Dotdigitalgroup/Email/etc/config.xml)
- also available on Magento Connect [link](http://www.magentocommerce.com/magento-connect/dotmailer-truly-integrated-email-marketing.html)

Compatibility
-------------
- Magento >= 1.6.2

# Contribution

You are welcome to contribute to dotmailer for Magento! You can either:
- Report a bug: create a [GitHub issue](https://github.com/dotmailer/dotmailer-magento-extension/issues/new) including description, repro steps, Magento and extension version numbers
- Fix a bug: please clone and use our [Testing branch](https://github.com/dotmailer/dotmailer-magento-extension/tree/testing) to submit your Pull Request
- Request a feature on our [community forum](https://support.dotmailer.com/hc/en-gb/community/topics/200432508-Feedback-and-feature-requests)

# V6.3.2 Release Notes
    - We’ve fixed a problem in which store views didn’t exist.
    - Bulk order sync now has a delay (of 60 mins) before being imported.
    - Trial account enhancements that we’ve already implemented in our Magento 2 connector are now also implemented for Magento 1.
    - We’ve fixed the collection filter for flat products.
    - External dynamic content wasn’t aligning centrally on mobile devices; it does now.
    - Changes in 'Subscriber' status weren't being sent back to Magento from dotmailer; this has been fixed.
    - We’ve implemented an IP address change.
    - Before creating a contact, an automatic check is made to ensure the API is enabled.
    - The class Zend_DB_Expr is now used for columns with expressions.
    - We’ve fixed a problem relating to the website ID that’s used when getting a store’s configuration in the helper.

# V6.3.1
###### Bug fixes
 - Unsubscribers from Magento weren’t being removed successfully from the subscriber address book; they are again now(#233).
 - Contact datafields weren't getting updated in dotmailer for existing customers in Magento whose data changed; this has been fixed.(#244)
  
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
 - Support for dotmailer regions
## Bug fixes
 - Bug fixed for fatal error on manual sync
## Improvments
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
 - Use dotmailer template for transactional emails.
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
* Cleaned the dotmailer_order_imported from db.
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
