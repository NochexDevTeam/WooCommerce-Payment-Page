=== WooCommerce Payment Gateway - Nochex ===
Contributors: Nochex 
Tags: WooCommerce, Credit Cards, Shopping Cart, Nochex Payment Gateway, Nochex, Extension, Gateway
Requires at least: 3.3
Tested up to: 6.6.1
Stable tag: 2.7.8
License: GPLv3 or later 
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Accept all major credit cards directly on your WooCommerce site using the Nochex payment gateway.

WooCommerce Version Tested up to 9.2.3 and WordPress 6.6.1

== Description ==
= Nochex Online Payment Services =
Website: http://www.nochex.com

Nochex is the UK's leading independent payment service for start-up, small and medium sized online merchants. We provide a simple and straightforward payment platform that makes 
it easy for your customers to use and for you to get paid, and we back that up with a world-class fraud prevention capability on our safe, secure and reliable PCI-compliant payment pages.

= Key Features =
* Quick and seamless integration into the WooCommerce checkout page.
* Accept all major credit cards.
* Prevent Fraud - use 3D Secure, 3D Secure is a standard developed by the card schemes - Visa and MasterCard - to improve the security of Internet payments.
* Risk Management - Our gateway is PCI Level 1 compliant.
* Automatically Update Orders - Use APC (Automatic Payment Confirmation) to update orders when they have been paid for.
* Mobile Payments - Mobile friendly interface for customers.
* Customers are sent to the secure Nochex payments pages to make a payment.

== Installation ==

= Installing The Payment Gateway Plugin =
* Download the plugin zip file.
* Login to your WordPress Admin. Click on Plugins | Add New from the left hand menu.
* Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
* Activate the plugin.
* Open the settings page for WooCommerce and click the "Payment Gateways" tab.
* Click on the sub tab for "Nochex".
* Configure your Nochex Gateway settings. See below for details.

= Connect to WooCommerce =
To configure the plugin, go to **WooCommerce > Settings** from the left hand menu, then the top tab "Payment Gateways". You should see __" Nochex"__ as an option at the top of the screen. 
__*You can select the radio button next to this option to make it the default gateway.*__

* ** Enable/Disable** - check the box to enable Nochex.
* ** Title** - allows you to determine what your customers will see this payment option as on the checkout page.
* ** Checkout Message** - controls the message that appears under the payment fields on the checkout page. Here you can list the types of cards you accept.
* ** Nochex Merchant ID / Email Address** - enter your Nochex account email address or Merchant ID.
* ** Payment Complete Status - Order status for successful transactions
* ** Payment and Order Mismatch Status - Order Status for a mismatch with transactions and orders, e.g. Amounts
* ** Payment Declined Status - Order Status for declined transactions
* ** Hide Billing Details** - optional feature, if this feature is enabled - the billing address details will be hidden when the customer is sent to Nochex.
* ** Nochex Test Mode** - optional feature, if this feature is enabled - check to enable Nochex test mode, uncheck to enable LIVE transactions.
* ** Detailed Product Information** - optional feature, if this feature is enabled - product information that is passed to Nochex will be displayed in a table-format opposed to a paragraph
* ** Nochex Show Postage** - optional feature, if this feature is enabled - The postage amount is displayed separately to the ordered amount.
* ** Debug Log** - a log will be kept of all important information regarding Nochex usage, e.g. customer sent to Nochex, whether or not APC has passed etc.
* ** Callback** - optional feature - This feature updates orders once a successful transaction has taken place. This works similarly to our APC system but in order to use this option you will need to contact Nochex Support.
* ** Save Changes.** 

== Changelog ==

= 2.8.1 =

- Minor update to remove unused functions and scripts

= 2.8.0 =

- Small change to APC script
  
= 2.7.9 =

- Small change to APC script
- Updated referenced variables.

= 2.7.8 =

- Small Change to APC script.

= 2.7.7 =

- Small change to APC system

- Added link to settings page on plugin menu

= 2.7.6.1 =

- Small fix to callback/apc functions to account for customers paying for previously falied orders

= 2.7.6 =

- Updated APC/Callback page

= 2.7.5.3 =

- Small update towards order status'

= 2.7.5.2 = 

- New Status's features to allow you to set specific status for each transaction status
	- Paid, Declined, and Order Mis-Match

= 2.7.5.1 =

- Update in relation to minor bug found.

= 2.7.5 =

- Tested up to Woocommerce 6.4.1 and WordPress 5.9.3
- Update in relation to order amounts less than a �1, when a coupon code is used.

= 2.7.4 =

- Tested up to Woocommerce 6.3.1 and WordPress 5.9.2
- Minor Bug Fixes.

= 2.7.3 =

- Tested up to Woocommerce 6.1.1 and WordPress 5.8.3
- Minor update to APC and Callback script

= 2.7.2 = 

- Updated ordered items function
- Tested up to Woocommerce 5.0.0 and WordPress 5.6.2

= 2.7.1 =

- Removed callback feature as it is not required, module already checks for callback / apc
- Tested up to WordPress 5.5 + Woocommerce 4.3.2

= 2.7 =

- Updated payment form functionality goes to payment receipt page.
- Removed unused variables.

= 2.6 =

- Separated the Payment Form "HTML" into a standalone file
- Separated the APC and Callback Script into a standalone file
- Separated the Payments Settings into a standalone file
	+ Added Validation for the payment gateway - If Nochex is enabled, check if merchant id / email is present.
- Updated each setting description, and placeholder
- Updated Logging Messages and a new link in the Payment Settings to go to the Nochex Log - ** Shows only if debugging is enabled **

= 2.5 =

* Bug Fixes *

- Updated Payment Form, and Debug options

= 2.4 =

* Bug Fixes *

- Updated the Payment Method description
- Updated the Payment Form presentation as some content on the page overlapped the payment form background
- Updated the APC / Callback Response / Order Notes

= 2.3 =

* Bug Fixes *

- Removed References to external resources
- Updated WordPress and WooCommerce references.
- Updated and Validated Input sent to Nochex, and data returned.

= 2.2 =

* Update - Fixed the product amount that was passed through to the payment page (Had been missing the Tax amount) Total amount was passed through correctly.

* Bug Fixes *

- If woocommerce was disabled then the Nochex module would produce an error message // Resolved
- Data collected about an order were using old / depreciated functions // Resolved
- APC / Callback - Optional_2 would show an error message if no value was returned // Resolved

= 2.1 =

* Update - Added Billing and Delivery Country Field to pre-populate the Billing and Delivery Country on the payment page*

= 2 =

* New Features and Settings *

- Detailed Product Information - Show a table structure on the payments page with details of products ordered.
- Callback - Use our callback functionality which works in a similar way to our APC. For more details checkout our <a href="https://support.nochex.com/">support system</a> If you would like to use this feature please contact us so we can able this on your Nochex account.
- Show Postage - Show postage separately to the Total on the Nochex Payment Page.

* Fixes *
Some minor updates to the APC system to ensure the module is TLS 1.2 ready.

= 0.1 =
* First Release.

== Upgrade Notice ==

= 0.1 =
* First Release.
