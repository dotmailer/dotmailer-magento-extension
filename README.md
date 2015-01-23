dotmailer for Magento Extension
==========================================

This module uses modman

First ensure you have modman installed (see here: https://github.com/colinmollenhour/modman#installation)
...and run the following in your magento root:
`modman init`
`modman clone https://github.com/dotmailer/dotmailer-magento-extension.git`

Don't forget to enable symlinks in:
`System->Configuration->Advanced->Developer->Template Settings`

Facts
-----
- community version.
- current version - V.2.0.4 [config.xml](https://github.com/dotagency/dotmailer_magento/blob/master/code/Dotdigitalgroup/Email/etc/config.xml)
- also available on Magento Connect [link](http://www.magentocommerce.com/magento-connect/dotmailer-truly-integrated-email-marketing.html)

Compatibility
-------------
- Magento >= 1.6.2

### Upgrade notice


### V3.3.0

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
- Bestsellers will select and filter the sellable items in collection.
- Not enough coupons generated.


### V3.2.0

- Transactional Emails Re-factor.
- System Status Dashboard

## Features

- System Status Dashboard.
- RFM Analysis.
- Api Status.
- Raygun Integration.
- Feed for new releases.
- Number of days to delete orders from order created date.
- Abandoned cart limit.
- Log for all API calls.
- Alternative abandoned baskets.
- Mailcheck integration

## Bug fixed

- Major Re-factoring.
- Cover all the code with testing.
- Automation when subscriber is not imported yet.
- Security improvement for api credentials.
- Campaigns for multi website.
- Mailcheck front end notice.
- Improve reset subscribers.
- GeoIp redirection for the dynamic content.
- Config table to store related data not to update on every request.
- Improve code for dynamic content.
- Transactional emails refactoring.
- Payment method for orders.
- Check for feature active for disabled accounts.
- Disable sync for not mapped address books.
- App emaulation to match the env the order.
- Subdomain dynamic urls.
- Buttons to run sync services.
- Ajax reset of the contacts for an updated address books.


### V3.1.0

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
- Auto map on website level.


### V.3.0.3

## Single customer sync.

* Single Customer Sync Button
* ROI fix
* Code standards re-factoring


### V.3.0.2

## Automation Studio.

* OAUTH & Menu
* Map product attribute-set to transactional data
* Tracking code SSL fix


### V.3.0.1

## Transactional Emails Post Release Update.

* Translations update
* Default values update
* Transactional emails enabled fix
* Manually map the customer ID field
* Emails Reports with website id field


### V.3.0.0 : 

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
* Product Recommendations styling.
* TE styling.
* Subscribers reset button.
* Custom data fields.
* New Lost Baskets.

## Fixes

* New SMS Fixes.
* Sync Orders by store.
* Subscribers multi website fix.
* Suppressed contacts for orders.
* Wishlist contact id update fix.
* Suppressed contacts per website.
* Dynamic content for multi-site (multi-currency).
* Transactional data key.
* Reset orders direct query.
* Naming convention for connector compatibility.


#### V.2.0.5 : 
* Use the Order statuses to send the SMS.


#### V.2.0.4 :

* Reset subscriber_imported for reimport.
* Suppressed contacts button in admin settings.
* Cleaned the dotmailer_order_imported from db.
* Fix for table prefix names.
* Duplicate email address.
* Website id table update for null values.
* Ignore deleted sales orders from email_order table.
* Dropping number of subscribers from address book.


#### V.2.0.3 :
* Delete transactional data using email address.
* Subscriber Sync - fix the contact id when to unsubscribe.
* Subscriber Sync Empty Request.
* Change the time format for filenames.