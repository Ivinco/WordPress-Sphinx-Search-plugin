<?php
/*
	Copyright 2008  &copy; Percona Ltd  (email : office@percona.com)

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
	 * Sphinx object
	 */
	var $sphinx;
	
	
	function SphinxSearch_Config()
	{	
		//load configuration
		$this->get_admin_options();
		
		
		//initialize sphinx object and set neccessary parameters
		$this->sphinx = new SphinxClient ();
		$this->sphinx->SetServer ( $this->admin_options['sphinx_host'], intval($this->admin_options['sphinx_port']) );
		$this->sphinx->SetWeights ( array ( 1, 1 ) );
		$this->sphinx->SetMatchMode ( SPH_MATCH_EXTENDED2 );
	}
	
	/**
    * Load and return array of options 
    *
    * @return array
    */
   	function get_admin_options() 
   	{
   		if (!empty($this->admin_options)) return $this->admin_options;
   		
   		$adminOptions = array(
                        'wizard_done'   => 'false',
   			'search_comments' => 'true',
   			'search_pages'    => 'true',
   			'search_posts'    => 'true',
   			
   			'excerpt_before_match' => '<b>',
   			'excerpt_after_match' => '</b>',
   			'excerpt_before_match_title' => '<u>',
   			'excerpt_after_match_title' => '</u>',
   			'excerpt_chunk_separator' => '...',
   			'excerpt_limit' => '256',
   			'excerpt_around' => '5',
   			
   			'sphinx_port' => '3312',
   			'sphinx_host' => 'localhost',
   			'sphinx_index' => 'wp_',

                        'sphinx_path' => '',
   			'sphinx_conf' => '',
   			'sphinx_indexer' => '',
   			'sphinx_searchd' => '',

   			'sphinx_searchd_pid' => '',
   			'sphinx_installed' => 'false',
   			'sphinx_path' => 'false',
   			'sphinx_running' => 'false',
   			'sphinx_need_reindex' => 'false', //flag for cron job to remind about update
   			
   			'strip_tags' => '',
   			'censor_words' => '',
   			
   			'before_comment' => 'Comment:',
   			'before_page' => 'Page:',
   			'before_post' => ''
   			
   			);
   		$this->admin_options = get_option($this->adminOptionsName);
   		if ($this->admin_options['sphinx_installed']){
   			$this->check_sphinx_running();
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
            $this->admin_options['sphinx_searchd_pid'] = $this->get_searchd_pid($this->admin_options['sphinx_conf']);
        }
     	update_option($this->adminOptionsName, $this->admin_options);
     }
     
     /**
      * Parse sphinx conf and grab path to search pid file
      *
      * @param unknown_type $sphinx_conf
      * @return unknown
      */
     function get_searchd_pid($sphinx_conf)
     {
     	$content = file_get_contents($sphinx_conf);
     	//pid_file		= {sphinx_path}/var/log/searchd.pid
     	if (preg_match("#\bpid_file\s+=\s+(.*)\b#", $content, $m))
     	{
     		return $m[1];
     	}
     	return '';
     }
     
     /**
      * Check running sphinx search daemon or not
      *
      * @param string $path
      * @return string
      */
     function check_sphinx_running()
     {
         if (file_exists($this->admin_options['sphinx_searchd_pid'])){
             $this->admin_options['sphinx_running'] = 'true';
             return true;
         } else {
             $this->admin_options['sphinx_running'] = 'false';
             return false;
         }
     }

     function get_sphinx_pid()
     {
         exec("cat {$this->admin_options['sphinx_searchd_pid']}", $res);
         if (empty($res)){
             return false;
         } else {
             return $res[0];
         }
     }
     
     function need_reindex($flag)
     {
     	if ($flag){
     		$fp = fopen(SPHINXSEARCH_REINDEX_FILENAME, 'w+');
     		fwrite($fp, '1');
     		fclose($fp);
     	}else{
     		if (file_exists(SPHINXSEARCH_REINDEX_FILENAME)){
     			unlink(SPHINXSEARCH_REINDEX_FILENAME);
     		}
     	}
     }
    
}
