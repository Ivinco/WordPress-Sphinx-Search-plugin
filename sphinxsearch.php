<?php
/*
Plugin Name: WordPress Sphinx Search Plugin
Plugin URI: http://www.ivinco.com/software/
Description: Power of Sphinx Search Engine for Your Blog!
Version: 1.0
Author: &copy; Ivinco Inc
Author URI: http://www.ivinco.com/

    Copyright 2008  &copy; Ivinco Inc (email : office@ivinco.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Define path to Plugin Directory
 *
 */
define('SPHINXSEARCH_PLUGIN_DIR', dirname(__FILE__));

/**
 * Define path to Sphinx Install Directory
 * Sphinx will install in Wordpress default upload directory
 *
 */
$uploaddir = get_option( 'upload_path' );
$uploaddir = trim($uploaddir);
if (empty($uploaddir)){
    $uploaddir = WP_CONTENT_DIR . '/uploads';
}
define('SPHINXSEARCH_SPHINX_INSTALL_DIR', $uploaddir.'/sphinx');

/**
 * Use latest sphinx API from Sphinx distributive directory 
 * otherwise use it from plugin directory which come with plugin 
 */
if (file_exists(SPHINXSEARCH_SPHINX_INSTALL_DIR.'/api/sphinxapi.php'))
	include_once(SPHINXSEARCH_SPHINX_INSTALL_DIR.'/api/sphinxapi.php');
else 
	include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/sphinxapi.php');
	
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/sphinxsearch_config.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/sphinxsearch_frontend.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/sphinxsearch_backend.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/sphinxsearch_sphinxinstall.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/WizardController.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/SphinxService.php');
include_once(SPHINXSEARCH_PLUGIN_DIR.'/php/SphinxView.php');
/**
 * load tags - each tag you can use in your theme template
 * see README
 */
include_once(SPHINXSEARCH_PLUGIN_DIR.'/tags/sphinxsearch_tags.php');
     	
/**
 * main Sphinx Search object
 */ 
$defaultObjectSphinxSearch = new SphinxSearch();

class SphinxSearch{
	
	/**
	 *  Config object
	 */
	var $config;
	
	/**
	 *  Frontend object
	 */
	var $frontend;
	
	/**
	 *  Backend object
	 */
	var $backend;
	
	/**
	 * Construct
	 *
	 * @return SphinxSearch
	 */
	function SphinxSearch()
	{

		$this->config = new SphinxSearch_Config();
                $this->sphinxService = new SphinxService($this->config);
		$this->backend = new SphinxSearch_BackEnd($this->config);		
		$this->frontend = new SphinxSearch_FrontEnd($this->config);
		
		//bind neccessary filters
		
		//prepare post results
		add_filter('posts_request', array(&$this, 'posts_request'));
		add_filter('posts_results', array(&$this, 'posts_results'));
		
		//return number of found posts
		add_filter('found_posts', array(&$this, 'found_posts'));
		
		//content filters 
		add_filter('post_link', array(&$this, 'post_link'));
		add_filter('the_permalink', array(&$this, 'the_permalink'));		
		add_filter('wp_title', array(&$this, 'wp_title'));
		add_filter('the_content', array(&$this, 'the_content'));
		add_filter('the_author', array(&$this, 'the_author'));
		add_filter('the_time', array(&$this, 'the_time'));
				
		//bind neccessary actions
		
		//action to prepare admin menu
		add_action('admin_menu', array(&$this, 'options_page'));
                add_action('admin_init', array(&$this, 'admin_init'));

                //ajax                
                if (!empty($_POST['action'])){
                    $wizard = new WizardController($this->config);
                    add_action('wp_ajax_'.$_POST['action'],
                            array(&$wizard, $_POST['action'].'Action'));
                }
		
		//frontend actions
		add_action('wp_insert_post', array(&$this, 'wp_insert_post'));
		add_action('comment_post', array(&$this, 'comment_post'));
		add_action('delete_post', array(&$this, 'delete_post'));
	}
	
	/**
	 * Replace post time to commen time
	 *
	 * @param string $the_time - post time
	 * @param string $d - time format
	 * @return string
	 */
	function the_time($the_time, $d='')
	{
		if (!is_search()) return $the_time;		
		return $this->frontend->the_time($the_time, $d);
	}
	
	/**
	 * Replace post author name to comment author name
	 *
	 * @param string $display_name - post author name
	 * @return string
	 */
	function the_author($display_name)
	{
		if (!is_search()) return $display_name;		
		return $this->frontend->the_author($display_name);
	}
	
	/**
	 * Correct date time for comment records in search results
	 *
	 * @param string $permalink
	 * @param object $post usually null so we use global post object
	 * @return string
	 */
	function post_link($permalink, $post=null)
	{
		if (!is_search()) return $permalink;		
		return $this->frontend->post_link($permalink, $post);
	}
	
	/**
	 * Clear content from user defined tags
	 *
	 * @param unknown_type $content
	 * @return unknown
	 */
	function the_content($content = '')
	{
		if (!is_search()) return $content;
		$content = $this->frontend->the_content($content);
		return $content;
	}
	
	/**
	 * Query Sphinx for search result and parse results return empty query for WP
	 *
	 * @param string $query
	 * @return string $query
	 */
	function posts_request($query)
	{	
		if (!is_search()) return $query;
		$_GET['s'] = stripslashes($_GET['s']);
		//Qeuery Sphinx for Search results
		if ($this->frontend->query() ){
			$this->frontend->parse_results();
		}
		//return empty query
		return '';
	}
	
	/**
	 * Generate new posts based on search results
	 *
	 * @param object $posts
	 * @return object $posts
	 */
	function posts_results($posts)
	{			
		if (!is_search() ) return $posts;
		return  $this->frontend->posts_results();
	}
	
	/**
	 * Return total number of found posts
	 *
	 * @param int $found_posts
	 * @return int
	 */
	function found_posts($found_posts=0)
	{
		if (!is_search()) return $found_posts;
		return $this->frontend->post_count;
	}
	
	/**
	 * Query frontend for new permalink
	 *
	 * @param string $permalink
	 * @return string
	 */
	function the_permalink($permalink = '')
	{
		if (!is_search()) return $permalink;		
		return $this->frontend->the_permalink($permalink);
	}
	
	/**
	 * Change blog title to: <keyword> - wp_title()
	 *
	 * @param string $title
	 * @return string
	 */
	function wp_title($title = '')
	{
		if (!is_search()) return $title;		
		return $this->frontend->wp_title($title);
	}
	
	/**
	 * Set flag for cron job to remind about update
	 *
	 * @param unknown_type $post_id
	 * @param unknown_type $post
	 */
	function wp_insert_post($post_id, $post='')
	{
		$this->sphinxService->needReindex(true);
                $options['sphinx_need_reindex'] = true;
		$this->config->update_admin_options($options);
	}
	
	/**
	 * Set flag for cron job to remind about update
	 *
	 * @param unknown_type $id
	 * @param unknown_type $data
	 */
	function comment_post($id, $data='')
	{
		$this->wp_insert_post(0, 0);
	}
	
	/**
	 * Set flag for cron job to remind about update
	 *
	 * @param unknown_type $id
	 */
	function delete_post($id)
	{
		$this->wp_insert_post(0, 0);
	}
     
	/**
	 * Show Admin Options
	 *
	 */
    function print_admin_page()
    {
    	$this->backend->print_admin_page();
    }
    
    /**
     * Bind printAdminPage to Show Admin Options 
     *
     */
    function options_page()
    {        
    	if (function_exists('add_options_page')) {
            add_options_page('Sphinx Search', 'Sphinx Search', 9, basename(__FILE__), array(&$this, 'print_admin_page'));
        }
    }

    function admin_init()
    {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', WP_PLUGIN_URL .'/'.
                dirname(plugin_basename(__FILE__)).
                '/templates/jquery-1.4.4.min.js');
        wp_enqueue_script( 'jquery' );
    }
}

register_activation_hook(__FILE__,'ss_install');
/**
* Install table structure
*
*/
function ss_install () 
{
    $config = new SphinxSearch_Config();
    $sphinxInstall = new SphinxSearch_Install($config);
    $sphinxInstall->setupSphinxCounterTables();
}

