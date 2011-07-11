=== pMailer Campaign Archive ===
Contributors: Pmailer
Donate link: http://www.pmailer.co.za/
Tags: pmailer, email, newsletter, archiver, archive
Requires at least: 3.0
Tested up to: 3.1.3
Stable tag: 0.1 

== Description ==

The pMailer campaign archive plugin displays a paginated list of all emails sent out in chronoligical order.
When one clicks on one of emails in the list, a new tab will be opened in the browser and the message will
be displayed.

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip our archive and upload the entire `pmailer_campaign_archive` directory to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings and look for "pMailer campaigns" in the menu
1. Enter your pMailer API URL and API Key and let the plugin verify it.
1. Now go to either a page or a post and add the following snippet of code: [pmailer_campaign_paginator] 
1. Save the page/post and browse and browse to the page/post on the site and you will the paginated list of emails sent.

== Frequently Asked Questions ==

= What messages does the campaign archiver display? =

The campaign archiver only displays messages that have been sent starting from the most
recent sent.

= Will my messages be indexed by search engines such as google? =
yes, the messages displayed are in-bedded into the html code and can be be indexed by search engines.

== Screenshots ==

1. Entering your pMailer login info
2. Inserting short code on a page
3. Mail campaigns paginated listing on site

== Upgrade Notice ==

= 0.1 =
* Initial release, no upgrade information available.

== Change Log ==

Initial release.