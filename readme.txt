=== WordPress Sphinx Search Plugin ===
Contributors: Ivinco
Donate link: http://www.ivinco.com/
Tags: search, sphinx
!Requires at least: 2.0.2
!Tested up to: 3.0.3
!Stable tag: r_2-0

WordPress Sphinx Search Plugin allows to use Sphinx Search Server power to enable ultra-fast and feature-rich search on WordPress-based websites. It is especially useful if your WordPress site becomes very large.

== Description ==

WordPress Sphinx Search Plugin allows to use Sphinx Search Server power to enable ultra-fast and feature-rich search on WordPress-based websites. It is especially useful if your WordPress site becomes very large.

Search results are more relevant and you can search in posts, pages and comments using flexible search syntax, quickly sort the results by freshness or relevance. This plugin comes with sidebar widgets to display the most recent searches and top and related search terms.

This plugin replaces WordPress’s built-in search functionality.

Features

 * Use flexible search syntax (see below)
 * Sort search results by Relevance or Freshness
 * Search in posts, pages and comments
 * Use extended search form to fine tune the results
 * Use sidebar widget for displaying Related/Top search terms
 * Use sidebar widget for displaying Latest search terms
 * Use Settings to style the keyword highlighting and search results
 * Sphinx configuration Wizard (via Settings)

Flexible search syntax

Sphinx allows you to use the following special operators in searchbox:

   1. Operator OR: hello | world
   2. Operator NOT: hello -world, hello !world
   3. Field search operator: @title hello @body worldThe following field operators are available:
      @title – search in title of post or page
      @body – search in body of post, page or comment
      @category – search in blog categories
   4. Phrase search operator: “hello world”
   5. Proximity search operator: “hello world”~10

Here’s an example query which uses all these operators:

"hello world" @title "example program"~5 @body python -(php|perl)

Read more on extended search syntax on Sphinx website: http://sphinxsearch.com/doc.html#extended-syntax

Support

This plugin is developed by [Ivinco](http://www.ivinco.com/ "Ivinco"). If you need commercial support, or if you’d like WordPress Sphinx Search Plugin customized for your needs, we can help. Visit [plugin website](http://www.ivinco.com/software/wordpress-sphinx-search-plugin/ "plugin website") for the latest news.
See release notes, report bugs and feature wishes on Launchpad: https://bugs.launchpad.net/wp-sphinx-plugin

E-mail:
opensource@ivinco.com

Website:
[Ivinco](http://www.ivinco.com/ "Ivinco")

== Installation ==

=== Requirements ===

    * WordPress 2.0.2 or higher
    * Sphinx Search 0.9.8 or higher
    * Ability to install Sphinx if not installed
    * Writable WordPress upload directory for Sphinx configuration files, logs and indexes

=== Installation guide ===
[Online step-by-step installation guide](http://www.ivinco.com/software/wordpress-sphinx-search-plugin/ "Step-by-step installation guide")

==== Install the plugin ====

   1. Unpack the plugin archive to wp-content/plugins folder of your WordPress installation
   2. Activate Sphinx Search plugin via WordPress Settings
   3. Make sure WordPress upload directory is writable by web server (by default WordPress is configured to use wp-content/uploads)
   4. Open Sphinx Search settings page and follow Wizard steps to setup Sphinx Search Server and plugin configuration
   5. After Wizard finished start Sphinx Search by pressing "Start Sphinx daemon"

==== Setup schedule ====
Setup scheduled jobs to re-index your website data periodically
To setup periodical re-indexing, you should run Wizard to create special schedule files.
The default location of these files is: /path/to/wp-content/uploads/sphinx/cron/. When wizard finishes, edit your Crontab file.
Use “crontab -e” command in the Linux terminal and add the following lines to your crontab:
#Wordpress Delta index update
#Following cron job update delta index every 5 minutes:
*/5 * * * * /usr/bin/php /path/to/wp-content/uploads/sphinx/cron/cron_reindex_delta.php
#Wordpress Main index update
#Following cron job update main index daily (at 0 hours and 5 minutes):
5 0 * * * /usr/bin/php /path/to/wp-content/uploads/sphinx/cron/cron_reindex_main.php

==== Setup templates and widgets ====
Extended search form on search results page
<?php if (function_exists('ss_search_bar'))
    echo ss_search_bar();/*put it in search page*/?>

To find out if the current post is comment
<?php if (function_exists('ss_isComment') )
    if (ss_isComment()) echo 'It is comment'; else echo '';?>

Highlight search term in post title
Use the following function instead of the_title()
<?php sphinx_the_title(); ?>

Extended search form at the sidebar
Use “Sphinx Search sidebar” widget or add it as template tag:
<?php if (function_exists('ss_search_bar'))
    echo ss_search_bar(true); /*put it in sidebar*/?>

Related/Top searches at the sidebar
Use “Sphinx Related/Top Searches” widget or add it as template tag:
<?php if (function_exists('ss_top_searches')) ss_top_searches(); ?>

Latest searches at the sidebar
Use “Sphinx Latest Searches” widget or add it as template tag:
<?php if (function_exists('ss_latest_searches')) ss_latest_searches(); ?>

==== Upgrade the plugin ====

   1. Unpack the plugin archive to wp-content/plugins folder of your WordPress installation
   2. Open Sphinx Search settings page and cancel Wizard to skip configuration

== Frequently Asked Questions ==

Q: What is Sphinx Search Server?
A: Sphinx is a full-text search engine which provides fast and relevant full-text search functionality. Read more on Sphinx website http://sphinxsearch.com

Q: How to install Sphinx Search manually?
A: To manually install Sphinx use the official Sphinx Search documentation.

Q: How to update the search index?
A: The best option to update search index is to setup cron job task for it. Also you may manually update search indexes through WordPress Sphinx Search administrative interface.


== Screenshots ==

none

== Arbitrary section ==

=== Semi live update - "main+delta" scheme ===

To enable semi-live index updates also known as "main+delta" scheme, 
the plugin will create the following table in your MySQL database:
# in MySQL
CREATE TABLE wp_sph_counter
(
    counter_id INTEGER PRIMARY KEY NOT NULL,
    max_doc_id INTEGER NOT NULL
);
If your WordPress installation's table prefix is not "wp_", substitute
the correct value.

=== Top and last search terms ===
In order to be able to store search statistics the plugin will run 
the following SQL query during the activation process:
# in MySQL
CREATE TABLE `wp_sph_stats` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`keywords` varchar(255) NOT NULL default '',
	`date_added` datetime NOT NULL default '0000-00-00 00:00:00',
	`keywords_full` varchar(255) NOT NULL default '',
	PRIMARY KEY  (`id`),
	KEY `keywords` (`keywords`)
);
If your WordPress installation's table prefix is not "wp_", substitute
the correct value.

=== Start Sphinx Search at boot ===
How to automatically start Sphinx Search daemon at boot:
In Debian based systems i.e. Ubuntu:
% update-rc.d "/path/to/bin/searchd --config /path/to/etc/sphinx.conf" defaults
In Redhat based systems i.e. Fedora:
% chkconfig --add "/path/to/bin/searchd --config /path/to/etc/sphinx.conf"

== Upgrade Notice ==

= 2.0 =
New configuration Wizard will help your easy setup sphinx configuration.
Enhanced backend interface with more clear layout.
Added keywords highlight of the_excerpt Wordpress tag.
Fixed plenty of Bugs.

== Changelog ==

= 2.0 =
* Added configuration wizard: You can automatically install or reinstall Sphinx
Search service through web interface
* Changed default installation directory - now sphinx installed in default
Wordpress upload directory
* Added shebang syntax to sphinx configuration file - it allow to load connection
parameters automatically and hide it from the public access
* Changed layout of backend interface
* Changed error handling. Hided all system output and instead added human readable
error messages.
* Added widget to display Top-n search keywords.
  If entered search keywords has relevant keywords in log, then display top relevant keywords in Top-n bar.
* Added widget to display Latest-n search keywords
* Added widget to display extended search form in sidebar
  It allow sort search resutls by Relevance or Freshness,
  search by posts, by comments and by pages,
  exclude posts, comments or pages from search results.
* Added keywords highlight for content of the_excerpt Wordpress tag
* Added automatic generation of cron files

= 1.0 =
Frontend
* Added Search powered by Sphinx Search engine
* Added sort search resutls by Relevance or Freshness
* Added search by posts, by comments and by pages.
* Added exclude posts, comments or pages from search results.
* Added display comments at search results page.
* Added search non-password protected pages only
* Added search only approved comments
* Added "Match Any" search ability - if no one results was found, then it try to search in "Match Any" mode.
* Added support for tag title of web page - it changed due to entered search keywords
* Added log of all search results, except empty results
* Added tag to display Top-n search keywords
* Added tag to display Latest-n search keywords
* Added relevant keywords support to the Top-n search keywords bar.

Backend
* Added support to Disable/Enable search by: comments, posts, pages
* Added support of keywords wrapper settings:
  * Add tag Before and After search keyword in body/title
  * Separator of result snippets
  * Snippet max length
  * Maximum number of words around keyword in snippet
  * Prefix before Posts, Comments and Pages title
  * List of phrases to cut from search results
* Added configuration for Sphinx index prefix
* Added configuration for Host
* Added configuration for Port
* Added configuration for Configuration file
* Added configuration for Searchd file
* Added configuration for Indexer file
* Added support to reindex all content manually
* Added support to stop/start search daemon
* Added support to install Sphinx Search through web interface