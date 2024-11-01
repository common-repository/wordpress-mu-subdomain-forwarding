=== WordPress MU Subdomain Forwarding ===
Contributors: hazardcell, DotAnimizers
Author URI: http://joejacobs.org/
Plugin URI: http://joejacobs.org/software/wordpress-mu-subdomain-forwarding/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3561852
Tags: subdomain, forwarding, wordpress mu, wpmu, profile, redirection
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 0.1.2

Turns a vanilla WordPress MU install into a subdomain forwarding site with options for hosted blogs and/or profiles.

== Description ==

This plugin turns a vanilla WordPress MU install into a subdomain forwarding site. It gives each user a subdomain and allows path forwarding to an external site, a hosted blog and/or a hosted profile. Meta tags, keywords and path forwarding to external sites are all supported.

Users should go to Dashboard->Subdomain to edit subdomain settings and activate/deactivate the blog and profile. The public profile can be edited at Users->Public Profile. The admin for the plugin is at Site Admin->Subdomain Forwarding.

Please read the FAQ and Known Issues/Limitations sections before installing the plugin.

== Installation ==

**Please read the FAQ and Known Issues/Limitations sections before installing the plugin**

1. First, you have to ensure that you have a fully working WordPress MU VHOST install.
1. Upload `subdomain_forwarding.php` to the `/wp-content/mu-plugins/` directory.
1. Upload `sunrise.php` to the `/wp-content/` directory. If there already is a sunrise.php there, you will have to merge the files.
1. Edit wp-config.php and uncomment the SUNRISE definition line:
	`define( 'SUNRISE', 'on' );`
1. Edit the .htaccess file in your WPMU root directory and insert the following line at the **bottom** of the file after any rewrite rules:
	`ErrorDocument 404 /index.php`
1. Login as the site admin and visit Site Admin->Subdomain Forwarding and edit the options there.
1. Visit Site Admin->Options and enable new registrations for **user accounts only**. The blog sign up will be handled by the plugin.

== Frequently Asked Questions ==

= Does this work with with both subdomain and subdirectory installs? =

No it only works with VHOST (subdomain) installs. A version for subdirectories is in the works.

= Can one user have more than one subdomain? =

Not with this version (0.1). A version with support for multiple subdomains is in the works.

= Will a blog be created for every user? =

No, the script just assigns a subdomain to the user but the user can sign up for a blog if he/she wants to.

= Will this plugin make any modifications to my database? =

Yes, one database table will be created for storing the subdomain settings. The profile page settings will be stored with the user meta. The plugin also modifies the blog path in the *blogs* table if necessary. All other core WordPress MU tables are not touched.

== Known Issues/Limitations ==

As this is a very early version there are a couple of minor issues that haven't been overcome yet:

* Users with blogs who try to login from the main site will have to login twice before they can access their control panel. This is due to some issues with the cookies that I have not been able to deal with just yet.
* The username field will be used as the subdomain. Users cannot *yet* choose a custom subdomain name.
* One user can only have one subdomain. Multiple subdomains are not supported *yet*.
* This version will only work with one domain name. It has not been tested on sites that have been setup with more than one domain name.
* This plugin works with **VHOST installs only**.
* Users will have to sign up for a normal account before they can get a blog.
* I've only tested this with clean installs so I'm not sure if it will work with a current install without modifying the database in some way.
* Currently, only users who sign up after the plugin is installed will get a subdomain. Again, I do not reccommend installing this version on a current install as it could cause problems.
