<?php
/*
    WordPress Sphinx Search Plugin by Ivinco (opensource@ivinco.com), 2011.
    If you need commercial support, or if you’d like this plugin customized for your needs, we can help.

    Visit plugin website for the latest news:
    http://www.ivinco.com/software/wordpress-sphinx-search-plugin  

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
 * It will be very good to rewrite it as Singleton, but in php 4 we have bad support of OOP to
 * write elegance singleton class
 *
 */

define('SPHINXSEARCH_REINDEX_FILENAME', ABSPATH.'/wp-content/uploads/need_reindex');

class SphinxSearch_Config
{
	/**
	 * We need unique name for Admin Options
	 *
	 * @var string
	 */
	var $adminOptionsName = 'SphinxSearchAdminOptions';
	
	/**
	 * Admin options storage array
	 *
	 * @var array
	 */
	var $admin_options = array();
	
	/**
	 * @var SphinxClient
	 */
	var $sphinx;

        var $_view = null;
	
	
	function SphinxSearch_Config()
	{	
		//load configuration
		$this->get_admin_options();
		
		
		//initialize sphinx object and set neccessary parameters
                $this->init_sphinx();

                $this->_view = new SphinxView();
	}

    /**
    * Sphinx server connector initialization
    * 
    * @return SphinxClient
    */
    function init_sphinx()
    {
        $this->sphinx = new SphinxClient ();
        $this->sphinx->SetServer ( $this->admin_options['sphinx_host'], intval($this->admin_options['sphinx_port']) );
        $this->sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2 );
        
        return $this->sphinx;
    }

    function get_view()
    {
        return $this->_view;
    }
	
	/**
    * Load and return array of options 
    *
    * @return array
    */
   	function get_admin_options() 
   	{
   		if (!empty($this->admin_options)) {
            return $this->admin_options;
        }
   		
   		$adminOptions = array(
            'wizard_done'   => 'false',
            
            'seo_url_all'     => 'false',
            
   			'search_comments' => 'true',
   			'search_pages'    => 'true',
   			'search_posts'    => 'true',
            'search_tags'     => 'true',
   			
   			'excerpt_before_match' => '<b>',
   			'excerpt_after_match' => '</b>',
   			'excerpt_before_match_title' => '<u>',
   			'excerpt_after_match_title' => '</u>',
   			'excerpt_chunk_separator' => '...',
   			'excerpt_limit' => '256',
   			'excerpt_around' => '5',
   			
   			'sphinx_port' => '9312', 
   			'sphinx_host' => 'localhost',
   			'sphinx_index' => 'wp_',

                        'sphinx_path' => '',
   			'sphinx_conf' => '',
   			'sphinx_indexer' => '',
   			'sphinx_searchd' => '',
                        'sphinx_max_matches' => 10000, //set the maximum number of search results

   			'sphinx_searchd_pid' => '',
   			'sphinx_installed' => 'false',
   			'sphinx_path' => 'false',
   			'sphinx_running' => 'false',
   			'sphinx_need_reindex' => 'false', //flag for cron job to remind about update
   			
   			'strip_tags' => '',
   			'censor_words' => '',
   			
   			'before_comment' => 'Comment:',
   			'before_page' => 'Page:',
   			'before_post' => '',

                        'sphinx_cron_start' => 'false',

                        'check_stats_table_column_status' => 'false'
   			
   			);
   		$this->admin_options = get_option($this->adminOptionsName);
   		if ($this->admin_options['sphinx_installed']) {
            $sphinxService = new SphinxService($this);
            if ( $sphinxService->is_sphinx_running() ){
                $this->admin_options['sphinx_running'] = 'true';
            } else {
                $this->admin_options['sphinx_running'] = 'false';
            }
   		}
                
        if ('' == get_option('permalink_structure')){
            $this->admin_options['seo_url_all'] = '';
        }

		if (!empty($this->admin_options)) {
			foreach ($this->admin_options as $key => $option){
				$adminOptions[$key] = $option;
			}
		}            
		update_option($this->adminOptionsName, $adminOptions);
		$this->admin_options = $adminOptions;
        return $adminOptions; 
     }
     
     /**
      * Update Options
      *
      * @param array $options
      */
     function update_admin_options($options='')
     {
     	if (!empty($options)){
     		$this->admin_options = array_merge($this->admin_options, $options);
     	}
     	if (!empty($this->admin_options['sphinx_conf']) && file_exists($this->admin_options['sphinx_conf'])){
            $sphinxService = new SphinxService($this);
            $pid_file = $sphinxService->get_searchd_pid_filename($this->admin_options['sphinx_conf']);
            $this->admin_options['sphinx_searchd_pid'] = $pid_file;
        }
     	update_option($this->adminOptionsName, $this->admin_options);
     }

     function get_option($opt)
     {
         if (isset($this->admin_options[$opt])){
            return $this->admin_options[$opt];
         }
         return false;
     }
     
     function get_plugin_url()
     {
         return 'options-general.php?page=sphinxsearch.php';
     }
    
}
