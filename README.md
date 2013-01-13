Buddy bbPress Support topic
===========================

This plugin allows you to let users define support topics. 
Once on the topic page, the author, group admin / mod, or super admin will be able to manage the status of the topics (Resolved, Not resolved, not a support topic). 
If you run bbPress 2.2.3, then you'll also be able to manage your support topics from the Topics backend menu.

Available in french and english. 


Configuration needed
--------------------

+ WordPress 3.5 and BuddyPress 1.6.3
+ Working in WordPress 3.5 and BuddyPress 1.7 Bleeding
+ Working in bbPress 2.2.3


Installation
------------

Before activating the plugin, make sure all the files of the plugin are located in `/wp-content/plugins/buddy-bbPress-Support-Topic` folder.


Customizing
-----------

If you're using bbPress 2.2.3, you can style the support mentions :
```css
/* for example ! */
.topic-not-resolved {
	color:red;
}

.topic-resolved {
	color:green;
}
```