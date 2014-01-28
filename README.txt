=== RAMP Plugin Example ===
Contributors: crowdfavorite, alexkingorg, devesine
Tags: RAMP
Requires at least: 3.3.2
Tested up to: 3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A heavily-documented example plugin for extending RAMP.  It transfers the email address from the General settings screen ("This address is used for admin purposes, like new user notification"), with much ado.

This plugin is not particularly useful in its own right; the admin notification email address does not frequently change, and the plugin requires that it be the only thing sent in a batch if it is to be sent.  However, the code does exercise a significant portion of the RAMP plugin functionality, and should serve as a reasonable base for developing a more helpful plugin.

== Installation ==

This plugin requires [RAMP](http://crowdfavorite.com/wordpress/ramp/) to function.

Upload it to the plugins directory (on both the source and destination RAMP servers) and activate it through the Plugins menu.

== Use ==

To see the plugin in action, assuming RAMP is already set up and working, make a change to the email address on the General Settings screen on the source (staging) server, then create a new RAMP batch.  At the bottom of the batch, in the Extras setting, the "RAMP Plugin Example" row should have a checkbox; ensure that this box is checked (and that no other checkboxes are checked).  Click Pre-flight Check, then Send Batch; once the batch is complete, the email address on the General Settings screen on the destination (production) server should now match the change made on the source server.
