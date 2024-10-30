=== Clickst Share ===
Contributors: clickst
Tags: sharing, tracking, analytics
Requires at least: 2.7
Tested up to: 3.0.1
Stable tag: 0.2.2

This plug-in provides click-to-share on all major social networks and email. Unlike other tools, it gives you powerful analytics.

== Description ==

The Clickst Plug-In is _the_ utility for sharing. Like other tools, it provides 
click-to-share on all major networks and email. Unlike other tools it tracks referrals 
and integrates with the Wordpress user system so you can:

*   find out which posts are the most popular
*   see which networks people are coming from
*   explore who your readers are and which ones have the most influence

Clickst gives you this information through a new dashboard with rich charts and
an interactive graph that captures how your readers are connected.

== Installation ==

To use the Clickst plug-in, follow these steps:

1. Create an account at http://click.st and note down your key and secret
1. Upload `clickst.php` to your `/wp-content/plugins/` directory
1. Activate the plugin through the Plugins menu in WordPress
1. Click on 'Clickst' under the 'Settings' menu and set your key and secret and tweak your preferences
1. Add `<?php clickst_buttons() ?>` inside your templates wherever `the_post()` is available
