=== CRM-service WP Forms  ===
Contributors: crmservice
Tags: crm, crm-service, crm service
Requires at least: 4.9
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.4.5
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate your forms easily to the CRM-service!

== Description ==

[CRM-service](https://crm-service.fi/) is the fastest all-in-one CRM on market, that enables Sales Force Automation, Customer Management Automation, Support, Project Management and Invoicing, with Product and Warehouse Management.

**CRM-service is the perfect business solution when performance matters.**

This plugin integrates with the CRM-service and sends form submissions to the CRM-service for easy lead and customer management! Our intuitive onboarding process will walk you through setting up the plugin. The first form integration can be done in few minutes with our simple dashboard.

**The plugin comes with multiple awesome features:**
- Support for the [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), the [WP Libre Form](https://wordpress.org/plugins/wp-libre-form/), the [Gravity Forms](https://www.gravityforms.com/) and more to come!
- Intuitive onboarding process on activation.
- Super simple dashboard for setting up integrations.
- We also have your back if form submission send to CRM-service somehow fails. We attempt to re-send the submissions for three times.
- Multiple checks that the plug-in works correclty and instructions how to fix possible issues.

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the ‘Plugins’ menu in WordPress
3. Follow our intuitive onboarding instructions
4. Start collecting your leads to the CRM-Service!

== Frequently Asked Questions ==

= What is CRM-service? =

CRM-service is cloud-based and fully featured web-based Digital Business Platform with numerous different [features](https://crm-service.fi/product/). Read more from our [website](https://crm-service.fi/).

= Do I need to be customer of the CRM-service? =

This plugin requires you to have a CRM-service instance and API access enabled. If you would like to test and use our fully featured web-based Digital Business Platform, please [contact us](https://crm-service.fi/contact/) and we will be in touch!

= What form plugins are supported? =

At the moment we support the [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), the [WP Libre Form](https://wordpress.org/plugins/wp-libre-form/) and the [Gravity Forms](https://www.gravityforms.com/). Support for other form plugins is being planned.

If you need a support for spesific form plugin, [contact us](https://crm-service.fi/contact/) and we'll see what we can do!

== Screenshots ==

== Changelog ==

All notable changes to this project will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

= 1.4.5 =
Release date: 2024-02-02

* Fix: Showing saved field connections with Gravity Forms
* Fix: Save form on the first time when creating new integration
* Fix: Contact Form 7 syntax for getting id of form submission

= 1.4.4 =
Release date: 2023-09-08

* Fix showing saved field connections

= 1.4.3 =
Release date: 2023-09-04

* Instead of listing saved connections, list all form fields and check if there is already a connection made. This allows modifying and adding new connections when form fields changes after making the first connections.

= 1.4.2 =
Release date: 2023-05-26

* Fix fatal error if headign to site health right after installing the plugin

= 1.4.1 =
Release date: 2023-05-26

* Fix release

= 1.4.0 =
Release date: 2023-04-09

* Fix: do not save fail to form entry before we have actually tried to send the data to CRM-service API
* Fix: various PHP warnings and notices
* Added: bedugging info to site health checks

= 1.3.2 =
Release date: 2022-04-27

* Fixed deprecated use of array_key_exists on object causing errors on PHP 7

= 1.3.1 =
Release date: 2021-12-09

* Added: Filter `crmservice_forms_resend_failed_submissions` to disable resending failed submissions, return false if you wish to do so
* Fixed: Limit the number of failed CF7/Flamingo submissions to get for resend at once

= 1.3.0 =
Release date: 2021-11-18
This release fixes Contact Form 7 integration issues with select and checkbox fields by introducing two new value formatters. These value formatters apply for other plugin intregations as well, but shouldn't affect the behaviour for current forms.

* Added: Format select type field in case its value is array, use the first array element as value
* Added: Format checkbox field in case its value is array that contains only one value, use the first and only arrat element as value
* Fix: Improved Gravity Forms failed submissions query to try fix performance issues

= 1.2.2 =
Release date: 2020-01-31
* Fix: Default value of crmservice_transient_keys to array

= 1.2.1 =
Release date: 2019-08-06
* Fix: Added support for old type relational user ID fields

= 1.2.0 =
Release date: 2019-07-01
* Added: Support for relational user ID fields
* Fix: Module field notices
* Improved: Show module field notices for selected field initially when editing existing integration

= 1.1.0 =
Release date: 2019-06-10
* Added: Feature to support sending pre-filled fields within form data
* Added: Support Gravity Forms sub-fields (eg. name, address)
* Fix: Sort module fields in alphabetical order
* Fix: Do not show Gravity Forms section -field when mapping fields
* Fix: Order Gravity Forms fields in same order than in form
* Improved: Styles on admin when adding new integration

= 1.0.1 =
Release date: 2019-03-11
* Fix: In some situations re-sending previously failed submissions failed, it's fixed on this version

= 1.0.0 =
Release date: 2018-05-11
* Initial release
