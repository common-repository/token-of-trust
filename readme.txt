=== Token of Trust: AI Identity Verification, Age Verification, and KYC  ===
Contributors: tokenoftrust
Tags: woocommerce, verify age, identity verification, kyc, fraud prevention
Requires at least: 5.3.0
Tested up to: 6.6.2
Requires PHP: 7.2.0
Stable tag: 3.3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Comply with KYC and Age requirements. Reduce Fraud. Frictionless AI identity verification solution for WordPress and WooCommerce.

== Screenshots ==

1. The 'account connector' is an embeddable component that invokes the Token of Trust verification workflow. The 'reputation summary' is an embeddable component that represents the current state of verification for a given user.
2. Token of Trust: AI Identity Verification, Age Verification, and KYC for WordPress works with WordPress core and also has deeper integrations with select WordPress Member Management Plugins.
3. The Token of Trust settings page is where website admins can enter their License Key and check the integration status with the Token of Trust Platform.

== Description ==

= SELL AGE-RESTRICTED GOODS • VERIFY IDENTITY • PREVENT FRAUD =

Your customers will check out faster with Token of Trust.

Seamlessly integrate within the WooCommerce checkout process or WordPress account registration. Token of Trust is used for:

* **[Identity Verification or KYC/AML](https://tokenoftrust.com/resources/integrations/wordpress-identity/?utm_source=wordpress&utm_medium=app&utm_campaign=wordpress&utm_content=wordpress.identity)** - Meet financial regulations in industries such as financial services, web3 or crypto, gambling, or healthcare.
* **[Age Verification Compliance](https://tokenoftrust.com/resources/integrations/wordpress-age/?utm_source=wordpress&utm_medium=app&utm_campaign=wordpress&utm_content=wordpress.age)** - Comply with regulations in industries such as tobacco, cannabis, alcohol, gaming, firearms, and more.
* **[Safety & Security (Fraud Prevention)](https://tokenoftrust.com/resources/integrations/wordpress-anti-fraud/?utm_source=wordpress&utm_medium=app&utm_campaign=wordpress&utm_content=wordpress.antifraud)** - Build trust with social media platforms, communities, marketplaces, and dating sites.

https://youtu.be/u9ga4OpzLoo

= FEATURES =
* Database Check First – Verify age without collecting images (US only).
* ID Document Verification when needed – over 5000 ID types from 240+ countries.
* Selfie-match optional.
* See Verification Results in your WooCommerce Order Summary.
* Returning Customers skip repeat verifications for 90 days.
* Trigger verification by product, location, or shipping methods.
* Support for multilingual and internationalization.


= HOW DOES THIS PLUGIN WORK? =
1. You set the rules (ie. Age is 21+, person is from XYZ country, billing and shipping address match, etc)
1. Token of Trust modal captures data and/or images within our secure platform
1. Users will be automatically verified or (optionally) manually approved by an administrator. Those who are rejected can be reviewed. Those rejected are given an explanation for their rejection and an opportunity to correct the problem.
1. A verification summary for each user can be accessed from the WordPress/WooCommerce admin dashboard or from Token of Trust’s web portal dashboard.

= DESIGNED FOR PRIVACY =

Token of Trust uses the latest security and encryption techniques to keep your business compliant and your consumer’s data private. Token of Trust is compliant with the following privacy regulations:

* GDPR (UK & EU)
* CCPA
* PIPEDA
* LGPD

= NEED HELP? =
If you have questions about setting up Token of Trust, contact us on our [Get Help](https://tokenoftrust.com/contact/contact-sales/?utm_source=WordPress&utm_medium=integration&utm_campaign=WordPress&utm_content=WordPress.org_plugin-page) form.

This plugin requires an active Token of Trust account. To create an account, download the Token of Trust WordPress plugin and click the “Get Started Now” button within the plugin in WordPress/WooCommerce.

= USING TOKEN OF TRUST WITH MEMBER MANAGEMENT PLUGINS =

The Token of Trust plugin for WordPress works best alongside member management plugins that establish user profiles and account settings pages within WordPress. The most commonly used member management plugins by our customers are:

*   BuddyPress
*   Ultimate Member

= OTHER INTEGRATIONS =

See [Token of Trust's WordPress Integration options and scenarios](https://tokenoftrust.com/resources/integrations/WordPress/?utm_source=WordPress&utm_medium=integration&utm_campaign=WordPress&utm_content=WordPress.org_plugin-page) for details on all the ways you can use Token of Trust with WordPress.

= LANGUAGES =

Token of Trust has been translated into the following languages:

* English (US)
* Spanish
* French
* Other languages available upon request

= WE LOVE FEEDBACK =

We're on a mission to help people make safe and smart decisions online. If you have an idea for how we can improve our plugin or platform, [Send us a Message](https://tokenoftrust.com/resources/integrations/WordPress/).


== Changelog ==

= 3.3.6 =

- Add popup to watch account-based-setup video.

= 3.3.5 =

- Add Watch Getting Started video.

= 3.3.4 =

- Fixed Get Started link on the plugins page.

= 3.3.3 =

- Non-functional changes and bug fixes.

= 3.3.2 =

- Fixed not finding appDomain for the old licenses.

= 3.3.1 =

- Eliminate the Live Site Domain from the Wordpress License & API settings page.

= 3.3.0 =

- Request Minimum Age in Setup and move age to general settings.

= 3.2.2 =

- Changed WordPress stepper to have account creation first and placement second.

= 3.2.1 =

- Added popup modal in WordPress that informs users about account creation step after selecting configuration.

= 3.2.0 =

- We ask before making a change to the Setup Wizard settings.

= 3.1.0 =

- Wordpress Setup Wizard Fixes
- prompts and Start Setup button now appear at the top of the page.

= 3.0.4 =

- Tested up to WordPress 6.6.2.

= 3.0.3 =

- Fixed a syntax error related to removal of quarantine.

= 3.0.2 =

- fixed the building process of the setup wizard.

= 3.0.1 =

- Reverted back to old QuickStart page
- we’re seeing an issue in the building process.

= 3.0.0 =

- Using setup wizard on the QuickStart page to improve the UX and make the steps clear.

= 2.1.2 =

- Update Hero image.

= 2.1.1 =

- Small fix to remove referer from API calls that caused problems for local development.

= 2.1.0 =

- Fixed to ensure meta-box is only added on order pages
- fixes collision with another plugin.

= 2.0.1 =

- Minor fix to mobile verification UX related to rotate button.
- Update to so that any allowedDomains can be used as live site domain

= 2.0.0 =

- Changes to make the plugin compatible with WooCommerce High-Performance Order Storage (HPOS).

= 1.27.1 =

- Minor change to fix a button event listener related to analytic events.

= 1.27.0 =

- Improved the Connect screen to ensure styling is consistent with TOT standards.

= 1.26.2 =

- Fix a bug where Wordpress wasn’t pulling product details from TOT backend.

= 1.26.1 =

- ProductSync on Wordpress now supports product variations.

= 1.26.0 =

- Changed a hook to ensure that we only auto-update minor releases of the major release you’re currently on.

= 1.26.0 =

- TOT now supports server side confirmation of cleared status ensuring that verification requirement cannot be bypassed by the client side prior to payment.

= 1.25.3 =

- Increased “Tested up to”.

= 1.25.2 =

- Update to the build process to allow change to display images.

= 1.25.1 =

- Fixes for labels and validation for ProductSync.

= 1.25.0 =

- use appUserId if there's no transactionId with orders

= 1.24.0 =

- Removing the first-time activation notice

= 1.23.2 =

- Remove the 'TOT Not Activated' on admin panel since it is not 100% true.

= 1.23.1 =

- Build related fix.

= 1.23.0 =

- refresh api keys by webhook

= 1.22.0 =

- attach process_order function to more woocommerce hooks

= 1.21.1 =

- increase the priority of process_order function in TOT

= 1.21.0 =

- use options for debug_mode instead of transients

= 1.20.0 =

- handling transient with caching plugins

= 1.19.1 =

- Fixes to turn off some overly verbose logging.

= 1.19.0 =

- Downgrade php dependencies related to error capture. Changed a php timeout.

= 1.18.0 =

- Updates to Verification Required screen

= 1.17.2 =

- Fixed a build problem.

= 1.17.1 =

- Fixed some issues on the QuickStart page.

= 1.17.0 =

- We now have links to ‘learn more’ and ‘get started’ links in wordpress quickstart.
- Removed link that directed customers to HQ without a use case.

= 1.16.0 =

- Improved error handling.

= 1.15.1 =

- Improved error handling.

= 1.15.1 =

- Ensure that tot-log isn't indicated for cache.

= 1.15.0 =

- Fixed a defect in the UX related to setup of verification gates in QuickStart.

= 1.14.0 =

- Updates to fix installation / activation problem.

= 1.13.2 =

- Updates to QuickStart.

= 1.13.1 =

- Fix for when there's more than one transactionLine with the same product.

= 1.13.0 =

- Now detecting the document type so we need to ask for it less likely.

= 1.12.1 =

- Non-functional fixes.

= 1.12.0 =

- HQ setting page supports activation / deactivation of back of ID.

= 1.11.1 =

- Disable sentry logging for now.

= 1.11.0 =

- Minor non-functional fixes.

= 1.10.1 =

- UX Improvements, simplifying the verification and improving conversion rates.
- Made back of Drivers License, Selfie compare optional.
- Bug fixes.

= 1.10.0 =

- Fix a crash condition for tax collection when a user is logged in and has no roles assigned.

= 1.9.0 =

- Simplified the verification process.
- Updated copy on our initial screens that indicate to the consumer why they are going thru verification.
- We have made no selfie, no back of dl and no name match the default configuration when customers sign up via self serve.
- Added the option to do remote logging support for premium support customers.
- Fixed a UX bug surround improperly tagging products

= 1.8.4 =

- Minor bug fixes.

= 1.8.3 =

- Fixed a build related issue that was causing the plugin to not be recognized by the 'Add Plugin' screen.

= 1.8.2 =

- Build fix. Non-functional.

= 1.8.1 =

- Fix internal build. Non-functional change.

= 1.8.0 =

- Refactor of authentication for verifications so everything goes thru the steps. Removed re-use of verifications when there is no user record because it caused issues for user later. Improved logging around authentication.
- Fixed defect where product selection in woo-commerce was not skipping consultative questions and providing the correct pricing plans.

= 1.7.5 =

- Non-critical bug fixes.

= 1.7.4 =

- Fixed build issues (non-functional).

= 1.7.2 =

- Minor copy edits.

= 1.7.1 =

- Minor fixes to quickstart.

= 1.7.0 =

- Added a Quickstart menu so that it's more clear what to do after you get started.

= 1.6.43 =

- Minor fix to readme.

= 1.6.42 =

- We introduced a feature that empowers consumers to resubmit their documents multiple times, guided by the feedback they receive on their document capture.

= 1.6.41 =

- Fixed links out to Token of Trust.

= 1.6.40 =

- Fixed an issue related to dynamic selection of api server.

= 1.6.39 =

- Minor: Fixed some release notes problems.

= 1.6.38 =

- New and improved demo that shows what Token of Trust can recognize from an ID. Available on Token of Trust's HQ page after WordPress registration and connection.

= 1.6.37 =

- Add a link to direct the user to tokenoftrust.com from the FAQ page.

= 1.6.36 =

- Temporarily reset release to version 1.6.32.

= 1.6.35 =

- Product Sync: is configurable and off by default.

= 1.6.33 =

- Product Sync: Requires that when new products are created we collect attributes related to excise taxes and synchronizes those with Token of Trust to ensure we can collect excise taxes in real time.
- Product Sync: Support for Import and Export
- Add an FAQ page natively in WordPress so that vendors don’t need to direct users to Token of Trust to get answers to common questions.

= 1.6.32 =

Updates for opt-in for charity:
- Allow configuration of the checkbox to default to opt-in OR not opt-in.
- Added reporting on what was collected for charity.
- Added export csv report on what was collected for charity.
- Fixed some styling issues.

= 1.6.31 =

- Fix a critical error when an invalid live site domain is entered.
- Fix an issue where we were incorrectly labelling some transactions Not Activated.

= 1.6.30 =

- Tested up to WordPress 6.2.
- Beta release of 'Round up for Charity'.
- Ensure the appUserid is passed along when user is signed in.
- Fix to ensure that if test servers are down - production api keys can still be fetched.

= 1.6.29 =

Tax collection fixes:

- Fixed to ensure wholesale transactions are always tagged as such.
- Fix to ensure traceIds don't change through the transaction.

= 1.6.28 =

Tax collection fixes:

- Fixed an issue where disabling verifications also disabled checkout tax collection.
- Added tax collection amounts to orders in a separate field.
- Fixed an issue where post-order 'audit' always failed California since it was looking for the wrong label.
- Fixed an issue where _tot_ordertype was not set to wholesale when a wholesaler was signed in.

= 1.6.27 =

- Reverted to 1.6.25 - we're seeing an issue where excise tax collection is turned off for some customers

= 1.6.26 =

- Added advanced setting for those using excise tax feature.

= 1.6.25 =

- Use the labels returned from the Core Product so that we can conform to CECET and other tax labelling requirements.
- Send the transactional sales price to the backend for tax calculations.
- Fix to wholesaler, retailer role matching.

= 1.6.24 =

- Fixed source_url included in links out to tokenoftrust.com that broke auto-connection.
- Fixed links out to sandbox that caused bad license registrations.
- Fixed a caching issue at checkout.

= 1.6.23 =

- Added a number of advanced options to support wholesale vs retail for excise tax calculations.
- Added ability to order on behalf of a customer on the admin page and include excise taxes.

= 1.6.22 =

- Display warning to admin if attempt is made to use the API key on an invalid domain.
- Revamp to passing transactionId and traceId along with Woo Commerce transactions to facilitate better logic to troubleshoot orders.
- Fix to a problem where the tot-status was incorrectly attributed to the latest post - which was often but not always correct.

= 1.6.21 =

- Fixes anchor to get started.

= 1.6.20 =

- Ensure traceId is stable from cart through fulfillment.
- Fix an issue where 'Connecting' from TOT didn't appear to work in some cases and didn't provide adequate feedback.

= 1.6.19 =

- Reverted to 1.6.17 from 1.6.18 which will come again in 1.6.20

= 1.6.18 =

- Made getting started more clear for new installs: how to proceed (Get Started) by hiding unnecessary detail and providing admin level visibility when TOT is not setup.
- Fixed an issue where TOT api was called in a loop - we now cache on the client side.
- Bust cache on plugin browser side includes.

= 1.6.17 =

- Support for improved onboarding experience from Token of Trust - HQ screen.
- Improved uniqueness of trace-id on shopping carts.

= 1.6.16 =

- Moved more logging toward new debug logging mechanism and increased timeout to 45 minutes.
- Added an advanced option to allow optional debounce of the payment button in case people are mashing it multiple times and the payment plugin isn't properly handling it. WARNING: this option may cause problems with payment providers that depend up simulated button clicks.

= 1.6.15 =

- Tested through 6.0.0 and fixed a couple minor warnings.

= 1.6.14 =

- Fix an issue where givenName and familyName were not getting set correctly and could come to TOT as '0' or '1'.

= 1.6.13 =

- Fix an issue where order of products in cart could cause verification to not trigger on mixed goods cart.

= 1.6.12 =

- Fixes post-checkout verification.

= 1.6.11 =

- Minor fix - make traceId unique to allow better issue tracking.

= 1.6.10 =

- Adds support to specify pages to include or exclude from verification by multi-select.
- Also added support to exempt accounts from verification based upon roles - allowing admins, etc to get to protected pages without verification.

= 1.6.9 =

- Links to TOT include conversion_source=WordPress to give better context to WordPress and WooCommerce users when signing up.
- Ability to exempt orders based upon roles (admin, etc) - allowing trusted agents to place orders without verification.

= 1.6.8 =
- Updated the stable tag related to the plugin release.

= 1.6.7 =
- The Token of Trust plugin will now keep your users' verifications even if they switch email addresses in your system.

= 1.6.6 =
- Excise Taxes will not calculate on bundles of other items, but rather the items themselves in the bundle.

= 1.6.5 =
- Better excise error handling

= 1.6.4 =
- Improved API Connection testing to provide better feedback in the Admin dashboard.

= 1.6.3 =
- Better reporting on ToT's performance within WooCommmerce.

= 1.6.2 =
- Added the ability for Token of Trust users to add excise taxes to the shopping card process. Contact our success team for more info.

== Installation ==

= From your WordPress dashboard =

1. Visit Plugins > Add New
2. Search for Token of Trust
3. Activate the Token of Trust plugin from your Plugins page.
4. Navigate to the new Token of Trust settings page in the WordPress admin menu
5. Complete the fields for production domain and your API keys.

= From WordPress.org or GitHub =

1. Upload the Token of Trust plugin folder to:
   `/wp-content/plugins/`
2. Activate the Token of Trust plugin from your Plugins page.
4. Navigate to the new Token of Trust settings page in the WordPress admin menu
5. Complete the fields for production domain and your API keys.

Once complete, the WordPress dashboard will contain an Account Connector widget. Any BuddyPress or Ultimate Member profiles will contain a Verifications tab and new shortcodes will be available.

For additional help and documentation for integrating Token of Trust components on WordPress sites, please visit the [WordPress Plugin Docs on our website](https://tokenoftrust.com/resources/integrations/WordPress/?utm_source=WordPress&utm_medium=app&utm_campaign=WordPress&utm_content=WordPress.org_plugin-page).

= Widget Shortcodes =

You can use the shortcodes below to render tot widgets where you want them. Short codes will default to the currently logged-in user.

= Account Connector =

Allows the logged in user to connect their account to token of trust. After connecting this shows their reputation (much like the Reputation Summary below) and allows navigation to Token of Trust to improve their reputation and configure their user.

Please Note: For security reasons this widget should only be shown on password protected pages for the intended user!

`
[tot-wp-embed tot-widget="accountConnector"][/tot-wp-embed]
`

To show the account connector using to the person API.

`
[tot-wp-embed tot-widget="accountConnector" verification-model="person"][/tot-wp-embed]
`

= Reputation Summary =

Displays a summary view of the user's reputation.

`
[tot-wp-embed tot-widget="reputationSummary"][/tot-wp-embed]
`

= Profile Photo =

Displays a given user's selected token of trust photo.

`
[tot-wp-embed tot-widget="profilePhoto"][/tot-wp-embed]
`

= Verified Indicator =

Displays a small indication of how far the user has gone through token of trust verification process.

`
[tot-wp-embed tot-widget="verifiedIndicator"][/tot-wp-embed]
`

To show this indication when members are not verified, use with this additional attribute.

`
[tot-wp-embed tot-widget="verifiedIndicator" tot-show-when-not-verified="true"][/tot-wp-embed]
`

= Additional Settings =

You can override any short code user by passing additional attributes as follows:

`
[tot-wp-embed wp-userid="EXAMPLE" tot-widget="reputationSummary"][/tot-wp-embed]
`

= Render in templates/PHP =

The easiest way to render widgets from templates is to use shortcodes just like in the WordPress admin interface

`
<?php

echo do_shortcode('[tot-wp-embed tot-widget="reputationSummary"][/tot-wp-embed]');
`

== Frequently Asked Questions ==

= Is Token of Trust compliant with the EU’s General Data Protection Regulation (GDPR)? =

Yes, Token of Trust maintains compliance with GDPR as a “Data Processor”. You may request Token of Trust’s Data Processing Addendum (DPA) by emailing [support@tokenoftrust.com](mailto:support@tokenoftrust.com).

= Do I have to create a Token of Trust account before using this plugin? =

Yes. This plugin connects your Token of Trust account to your WordPress site using a license key and requires that you [create a Token of Trust account](https://tokenoftrust.com/resources/integrations/WordPress/?utm_source=WordPress&utm_medium=app&utm_campaign=WordPress&utm_content=WordPress.org_plugin-page) to get started. A credit card is not required to try it out. All Token of Trust accounts start in test-mode, allowing free testing without affecting your live data. You can switch from test-mode to live-mode whenever you’re ready for launch.

= Does Token of Trust support age verification? =

Yes. Token of Trust is capable of confirming a person's identity, determining their age, and checking if they meet a specific age criteria (e.g. 21+). Token of Trust can support websites with minimum age requirements. This may apply to e-commerce merchants selling age-restricted products, such as alcohol, tobacco, vape and firearms.

= Does Token of Trust verify government-issued photo IDs like a Passport? =

Yes. Official government IDs like a Passport or National ID Card can be captured and analyzed within Token of Trust’s verification workflow.

= Can I choose where verification displays for an advanced or custom application? =

Yes. We do support advanced shortcodes, javascript embeds and manual PHP rendering tools. See our website for more details on [Token of Trust's WordPress Integration options and scenarios](https://tokenoftrust.com/resources/integrations/WordPress/?utm_source=WordPress&utm_medium=app&utm_campaign=WordPress&utm_content=WordPress.org_plugin-page).

= What integrations do you support for building communities, marketplaces and other types of member management? =

*   BuddyPress
*   Ultimate Member

= What factors contribute to a member's identity verification? =

Token of Trust verification looks at a variety of attributes across multiple social networks and real world IDs like driver’s licenses. Some attributes include:

*   Account age
*   Activity location, geo tags and meta other metadata
*   Age verification
*   Name verification
*   Social fraud network scans
*   Government fraud lists
*   ID property consistency and security features
*   Electronic ID Verification (eIDV)

= Can I run Token of Trust from localhost? =

Yes we currently support running from any of the following localhost ports with our test keys: 80, 443, 3000, 3001, 3443, 7888, 8000, 8080, 8888, 32080, 32443, or 33080.

The Token of Trust plugin automatically detects when you're running on localhost so no configuration change is required - your Live Site always remains your production site.
