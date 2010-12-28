=== WordPress Sphinx Search Plugin ===
Contributors: Ivinco
Donate link: http://ivinco.com/
Tags: search, sphinx
!Requires at least: 2.0.2
!Tested up to: 3.0.3
!Stable tag: 4.0

This software replaces Wordpress's built-in search functionality with
the Sphinx Search Engine, which gives high-performance, relevant
search results.


== Description ==

We are open for features requests for plugin and ready to accept community patches.

You can monitor current tasks and further plans on the [WordPress Sphinx Plugin Launchpad project](https://launchpad.net/wp-sphinx-plugin).
You can also request [features](https://blueprints.launchpad.net/wp-sphinx-plugin) and  report [bugs](https://bugs.launchpad.net/wp-sphinx-plugin) there.

Frontend features

- Search powered by Sphinx Search engine or SphinxSearch inside
- Very fast search
- Flexible [search syntax](http://www.sphinxsearch.com/doc.html#extended-syntax)
- Sort search resutls by Relevance or Freshness
- Search by posts, by comments and by pages.
- Exclude posts, comments or pages from search results.
- Display comments at search results page.
- Search non-password protected pages only
- Search only approved comments
- By default search working in extended mode, but if no one results was found, then it try to search in "Match Any" mode.
- On search results page title of web page are changed due to entered search keywords
- Log all search results, except empty results
- Display Top-n search keywords
- Display Latest-n search keywords
- If entered search keywords has relevant keywords in log, then display top relevant keywords in Top-n bar.

Backend features

- Disable/Enable search by: comments, posts, pages
- Keywords wrapper settings:

  * Add tag Before and After search keyword in body/title
  * Separator of result snippets
  * Snippet max length
  * Maximum number of words around keyword in snippet
  * Prefix before Posts, Comments and Pages title
  * List of phrases to cut from search results

- Sphinx daemon Configuration:
 * Sphinx index prefix
 * Host
 * Port
 * Configuration file
 * Searchd file
 * Indexer file

- Indexer: You can reindex all content manually
- Search daemon: You can stop/start search daemon
- Sphinx Search configuration wizard: You can automatically install or reinstall Sphinx Search service through web interface

E-mail:
opensource@ivinco.com

Website:
http://ivinco.com/

== Installation ==

To use the power of Sphinx for your WordPress blog, follow these steps:
1. Install the Sphinx Search plugin.
2. Install Sphinx on your web server.(optional - you may install Sphinx through web interface of plugin)
3. Set up cron jobs to re-index your website periodically.
4. Configure the Sphinx Search plugin.
5. Customize your search form on [search results] page by ss_search_bar() tag.
6. Customize your sidebar with Search widgets (Top/Last search terms and extended search form).

Install the Sphinx Search plugin:
1. Unpack the plugin archive to wp-content/plugins/sphinxsearch folder of your WordPress installation;
2. Activate SphinxSearch plugin via WordPress admin area;
3. Make sure Wordpress upload directory is writeable by webserver;
4. Open Sphinx Search settings page and follow by Wizard steps to setup sphinx search.

Install Sphinx on your web server:
If you haven't already installed Sphinx, you can install it
automatically or manually:
1. For automatic installation go to 'WP-Admin -> Options -> SphinxSearch' and
   run configuration Wizard by pressing "Run configuration Wizard";
2. For manual installation - please visit http://www.sphinxsearch.com/ for more instructions.
3. After you have installed Sphinx manually change your sphinx.conf:
    Go to 'WP-Admin -> Options -> SphinxSearch' and run Wizard again.
    On second step specify path to installed search and indexer binaries.
    Follow by Wizard steps to complete configuration.
4. After Wizard finished start sphinx daemon by pressing "Start Sphinx daemon";
   
Set up cron jobs to re-index your website periodically:
1. Copy cron/reindex_config_sample.php to cron/reindex_config.php
2. Open reindex_config.php in editor and change path to Sphinx Indexer, Sphinx conf and index prefix (optional)
3. Use "crontab -e" to add the following lines to your crontab:
#Delta index.
#Run cron job every 5 minutes to update delta index:
*/5 * * * * /usr/bin/php /path/to/wp-content/plugins/sphinxsearch/cron/reindex.php delta
#main indexing.
#Run cron job once a day to update main index:
#Run every day in 0 hours and 5 minutes
5 0 * * * /usr/bin/php /path/to/wp-content/plugins/sphinxsearch/cron/reindex.php main

Configure the Sphinx Search plugin:
1. You can control the majority of options via admin area of WordPress. 
   Go to "WP-Admin -> Options -> SphinxSearch" to do it.

Customize your WordPress templates to show the Sphinx search field:
Sidebar Widgets:
Go to "WP-Admin -> Appearance -> Widgets" there are three widgets:
1. Sphinx Top searches
2. Sphinx Last searches
3. Sphinx Search sidebar

Template tags:
You can use the following tags in your templates:
1. Search form in sidebar:
  <?php if (function_exists('ss_search_bar')) echo ss_search_bar(true); /*put it in sidebar*/?>
2. Search form on the top of search results:
  <?php if (function_exists('ss_search_bar')) echo ss_search_bar();/*put it in search page*/?>
3. Top search results:
  <?php if (function_exists('ss_top_searches')) ss_top_searches(); ?>
4. Latest search results:
  <?php if (function_exists('ss_latest_searches')) ss_latest_searches(); ?>
5. To find out if the current post is comment:
  <?php if (function_exists('ss_isComment') ) if (ss_isComment()) echo 'It is comment'; else echo '';?>
6. If you want to use tags in post title - please use next function instead of the_title():
  <?php sphinx_the_title(); ?>


== Frequently Asked Questions ==

none

== Screenshots ==

none

== Arbitrary section ==


Search options:
Sphinx extended queries syntax allows the following special operators to be used:
* operator OR:
hello | world
* operator NOT:
hello -world
hello !world
* field search operator:
@title hello @body world
* phrase search operator:
"hello world"
* proximity search operator:
"hello world"~10
Here's an example query which uses all these operators:
"hello world" @title "example program"~5 @body python -(php|perl)
More information about extended syntax you can find at: http://sphinxsearch.com/doc.html#extended-syntax

The following field operators are available:
@title - search in post or page title
@body - search in post, page, comment body
@category - search search in blog categories


Semi live update - "main+delta" scheme:
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

Top and last search terms:
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

The plugin folder structure:
./cron - contains crontab scripts, see crontab instructions below;
./php - contains php classes and libs;
./tags - contains sphinx search tags implementation;
./templates - contains html templates, that you can change to suit more to your blog design;
./widgets - contains sphinx widgets;

How to automatically start Sphinx search daemon at boot:
In Debian based systems i.e. Ubuntu:
% update-rc.d "/path/to/bin/searchd --config /path/to/etc/sphinx.conf" defaults
In Redhat based systems i.e. Fedora:
% chkconfig --add "/path/to/bin/searchd --config /path/to/etc/sphinx.conf"
