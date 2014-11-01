=== Buddy-bbPress Support Topic ===
Contributors: imath
Donate link: http://imathi.eu/donations/
Tags: BuddyPress, bbPress, support, topic
Requires at least: 4.0
Tested up to: 4.0
Stable tag: 2.0
License: GPLv2

bbPress plugin to manage your support requests.

== Description ==

Once the plugin activated, go into your forums administration menu and define for each one of them if they will :

1. eventually accept support topic (default setting)
1. be dedicated to support
1. not accept support topics.


In the first case, a checkbox will be displayed to topic authors in order to let them define their topic as a support one.
In the second one, no checkbox will be displayed, and the topic will automatically be a support request.

For these 2 cases, once on the topic page, the author, group admin / mod, or super admin will be able to manage the status of the topics (Resolved, Not resolved..).

In the third case, no checkbox will be displayed and topics will be regular ones.

Since 2.0, the plugin requires **bbPress 2.5**.

Administrators are able to manage the support topics from the Topics backend menu. Once on the admin edit screen of a topic, in the first right metabox, they can change the status of the topic. They can also bulk edit support status from the list of topics using the edit bulk action.

It will work with BuddyPress (2.0) Groups forums (powered by bbPress 2.5).

It's available in french and english.

You can watch a demo below :

http://vimeo.com/110661175

== Installation ==

You can download and install Buddy bbPress Support Topic using the built in WordPress plugin installer. If you download Buddy bbPress Support Topic manually, make sure it is uploaded to "/wp-content/plugins/buddy-bbpress-support-topic/".

Activate Buddy bbPress Support Topic in the "Plugins" admin panel using the "Network Activate" (or "Activate" if you are not running a network) link.

== Frequently Asked Questions ==

= How can i add custom support status ? =
You'll need to use a filter from your plugin or the functions.php file of your active theme. Here's a <a href="https://gist.github.com/imath/9e69b8139ff6f7a4120a">gist</a> to illustrate how to achieve it.

= Does the plugin still work with BuddyPress legacy forums ? =
No, the plugin requires bbPress 2.5 which also works great to power BuddyPress Groups forums.

= If you have any other questions =

Please add a comment <a href="http://imathi.eu/tag/buddy-bbpress-support-topic/">here</a>

== Screenshots ==

1. Forum preferences.
2. Bulk edit topics support status.
3. BuddyPress Groups manage tab.
4. Custom support status.
5. New widget

== Changelog ==

= 2.0 =
* No more support for bbPress 1.x
* More control on the support feature which can now be managed from the parent forum
* Email notifications for moderators in case of a new support topic
* New BuddyPress manage group tab (requires BuddyPress 2.0+) to manage the support feature for the group forum (bbPress 2.5+)
* Topics support status bulk edit
* a WordPress filter to add new support status
* Now available for each blog of the network

= 1.1 =
* Stats in a new section of bbPress Right Now Dashboard Widget
* Sidebar Widget to display the stats on front end
* Topics can be filtered by support status on front and in back end
* Solves a bug in BuddyPress forums (bbPress 1.2) when used as sitewide one (ajax filter)

= 1.0.2 =
* brings more security

= 1.0.1 =
* fixes a bug when themes use their own templates to override bbPress 2.2.4 ones
* adds a filter in order to style the support mention in bbPress 1.2 BuddyPress group forums

= 1.0 =
* Plugin's first appearance in WordPress repo

== Upgrade Notice ==

= 2.0 =
Requires bbPress 2.5. Make sure to back up your database before upgrading the plugin.

= 1.1 =
Make sure to back up your database before upgrading the plugin.

= 1.0.2 =
security release, please upgrade.

= 1.0.1 =
dont worry, be happy :)

= 1.0 =
no upgrade, just a first version..
