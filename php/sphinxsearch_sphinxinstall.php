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

class SphinxSearch_Install
{
	/**
	 * Directory location of Sphinx Search plugin
	 *
	 * @var string
	 */
	var $plugin_sphinx_dir = '';
	
	/**
	 * Latest Sphinx Search archive filename
	 *
	 * @var string
	 */
	var $latest_sphinx_filename = 'sphinx-0.9.9.tar.gz';
	
	/**
	 * File location to latest Sphinx Search repository without filename
	 *
	 * @var string
	 */
	var $latest_sphinx_rep_loc = '';
	
	/**
	 * Config object
	 */
	var $config = '';

        function  SphinxSearch_Install(SphinxSearch_Config $config)
	{
		$this->__construct($config);
	}

	/**
	 * Constructor
	 *
	 * @param SphinxSearch_Config $config
         * @return void
	 */
	function  __construct(SphinxSearch_Config $config)
	{
		$this->config = $config;
		
		$this->plugin_sphinx_dir = SPHINXSEARCH_PLUGIN_DIR;
		$this->latest_sphinx_rep_loc = $this->plugin_sphinx_dir.'/rep/';
	}
	
	/**
	 * Return installation directory for Sphinx
	 * by default it /wp-content/uploads/sphinx_install/
	 *
	 * @return string
	 */
    function get_install_dir()
    {     	
        //save install dir in admin options
	$this->config->admin_options['sphinx_path'] = SPHINXSEARCH_SPHINX_INSTALL_DIR;
		
     	return $this->config->admin_options['sphinx_path'];
     }
     
	/**
     * Return sphinx file name, first check if available online copy
     * if not check local copy
     *
     * @return string
     */
     function get_sphinx_source()
     {	
     	if (!file_exists($this->latest_sphinx_rep_loc.$this->latest_sphinx_filename)){
     		return array('err' => 'Installation: Sphinx repository '. 
                            $this->latest_sphinx_rep_loc .
                        ' does not exist or is not available.');
     	}else {
     		$sphinx_source = $this->latest_sphinx_rep_loc;
     	}
     	return $sphinx_source;
     }
     
     /**
      * Rewrite reserver variables in config sphinx.conf to real values
      *
      * @param string $filename - path to sphinx.conf
      * @return bool or array
      */
     function rewrite_config_variables($filename)
     {
        if( !is_readable($filename) || !is_writeable($filename) ){
     		return array('err' => 'Installation: '.$filename.' is not writeable.');
     	}

        $template_content = file_get_contents($filename);

     	$rewrited_content = $this->generate_config_content($template_content);
     		
     	//file_put_contents in PHP 5
     	$fp = fopen($filename, 'w+');
     	fwrite($fp, $rewrited_content);
     	fclose($fp);
     		
     	return true;
     }

     function generate_config_content($template_content)
     {
         global $wpdb, $table_prefix;

          $sql_sock = '';
          if ('' != trim(ini_get('mysql.default_socket'))){
              $sql_sock = 'sql_sock = '.ini_get('mysql.default_socket');
          }
          $wizard = new WizardController($this->config);

     		/**
     		 * We have to rewrite following variables:
     		 * {sql_sock} to database socket
     		 *
     		 * {source}   to Sphinx Index name
     		 * {sphinx_path} to Sphinx Server root dir
     		 * {searchd_port} to Sphinx search daemon port
     		 * {wp_posts} to Wordpress posts table
     		 * {wp_comments} to Wordpress comments table
                 * {path_to_php} path to php executable file
     		 * {path_to_wp_config_php} path to wp-config.php - requiered by shebung syntax
     		 */
     	$search = array(
     		'{sql_sock}' => $sql_sock,
     		'{prefix}'   => $this->config->admin_options['sphinx_index'],
     		'{sphinx_path}' => $this->config->admin_options['sphinx_path'],
     		'{searchd_port}' => $this->config->admin_options['sphinx_port'],
     		'{wp_posts}' => $wpdb->posts,
     		'{wp_comments}' => $wpdb->comments,
     		'{wp_term_relationships}' => $wpdb->term_relationships,
     		'{wp_term_taxonomy}' => $wpdb->term_taxonomy,
     		'{wp_terms}' => $wpdb->terms,
                '{path_to_php}' => $wizard->detect_program('php'),
                '{path_to_wp_config_php}' => dirname(dirname(dirname($this->plugin_sphinx_dir))),
                '{max_matches}' => $this->config->admin_options['sphinx_max_matches']
     		);

     	$rewrited_content = str_replace(array_keys($search), $search, $template_content);
        return $rewrited_content;
     }

     /**
      * Install Sphinx Server
      *
      * @return array if error or bool true if success
      */
     function install()
     {	
     	global $table_prefix, $wpdb;
     	set_time_limit(0);
     	
     	//////////////////
     	//Get Source Filename
     	//////////////////
  		
     	$sphinx_source = $this->get_sphinx_source();
     	if ( is_array($sphinx_source) ){
            return $sphinx_source;
     	}
     	
	//////////////////
     	//Get Destination dir
     	//////////////////
     	
	$dir_inst = $this->get_install_dir();        

	if ( is_array($dir_inst) ){
		return $dir_inst;
        }

        $parentInstDir = dirname($dir_inst);
        if (!file_exists($parentInstDir)){
            return array('err' => "Installation: Directory ". $parentInstDir .
                        " does not exist. You need to setup WordPress upload ".
                        "directory (by default Wordpress is configured to use wp-content/uploads)");
        }

        if (!is_writable($parentInstDir)){
            return array('err' => "Installation: Directory ". $parentInstDir .
                        " is not writeable, ".
                        " check the permissions.");
        }

        if (!file_exists($dir_inst)){
            if ( !mkdir($dir_inst) ){
                return array('err' => "Installation: Can not create directory ". $dir_inst .
                        " check the permissions.");
            }
        } else {
            if (!is_writable($dir_inst)){
                return array('err' => "Installation: ". $dir_inst ." is not writeable, ".
                        " check the permissions.");
            }
            //clear previouse installations
            //exec("rm -fr " . $dir_inst.'/*');
        }

	//////////////////
     	//Copy Source Filename
     	//to destionation dir
     	//////////////////
     	
	$res = copy($sphinx_source.$this->latest_sphinx_filename, $dir_inst.'/'.$this->latest_sphinx_filename);
	if ($res == false) {
            return array('err' => "Installation: Can not copy ".
                $sphinx_source.$this->latest_sphinx_filename." to ".
                $dir_inst.'/'.$this->latest_sphinx_filename.
                    ", check the file permissions.");
	}
		
	//////////////////
     	//Extract Archive
     	//with repository
     	//////////////////
            chdir($dir_inst);
            $openarch = "tar xzf ".$dir_inst.'/'.$this->latest_sphinx_filename . " -C $dir_inst";
            exec($openarch, $output, $retval);
            if ($retval != 0)
                    return array('err' => 'Installation: Archive extracting failed: '.
                        $this->latest_sphinx_filename . ' !<br/>'.
                        'Command: '.$openarch."<br/>".
                        "try running it with sudo if it doesn't work");
                    
            $dir_rep = str_replace('.tar.gz', '', $this->latest_sphinx_filename);
            chdir($dir_inst.'/'.$dir_rep);
		
	//////////////////
     	//Run:
     	//./configure
     	//make & make install
     	//////////////////

            //configure
            $command = "./configure --with-mysql --prefix=$dir_inst 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Sphinx installation error, try to run this command manually in Terminal:';
                //echo '<script>alert("'.$msg.'")</script>';
                return  array('err' => $msg.'<br/>Command: '.$command.
                    " at the directory:".$dir_inst.'/'.$dir_rep."<br/>".
                        "try running it with sudo if it doesn't work");
            }

            //making
            $command = "make 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Installation: Sphinx installation error, try to run this command manually in Terminal:';
                //echo '<script>alert("'.$msg.'")</script>';
                return  array('err' => $msg.'<br/>Command: '.$command.
                    " at the directory:".$dir_inst.'/'.$dir_rep."<br/>".
                        "try running it with sudo if it doesn't work");
            }

            //make install
            $command = "make install 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Installation: Sphinx installation error, try to run this command manually in Terminal:';
                //echo '<script>alert("'.$msg.'")</script>';
                return array('err' => $msg.'<br/>Command: '.$command.
                    " at the directory:".$dir_inst.'/'.$dir_rep."<br/>".
                        "try running it with sudo if it doesn't work");
            }


            if (!file_exists($dir_inst.'/bin/indexer') || !file_exists($dir_inst.'/bin/searchd')){
                $msg = "Installation: indexer ({$dir_inst}/bin/indexer) or search daemon ({$dir_inst}/bin/searchd) was not found.";
                //echo '<script>alert("'.$msg.'")</script>';
                return array('err' => $msg);
            }
		
	//////////////////
     	//copy our config to 
     	//new installation
     	//////////////////
     	
	//copy our config to new installation
	$res = copy($this->plugin_sphinx_dir.'/rep/sphinx.conf', $dir_inst.'/etc/sphinx.conf');
	if ($res == false){ 
	    return array('err' => "Installation: Can not copy ".$this->plugin_sphinx_dir.'/rep/sphinx.conf'." to ".
                $dir_inst.'/etc/sphinx.conf'.", check the file permissions.");
	}
		
	if (file_exists($dir_inst.'/bin/indexer') && 
            file_exists($dir_inst.'/etc/sphinx.conf') &&
            file_exists($dir_inst.'/bin/searchd')){
                $admin_options = array(
				'sphinx_conf' 		=> "{$dir_inst}/etc/sphinx.conf",
				'sphinx_indexer'	=> "{$dir_inst}/bin/indexer",
				'sphinx_searchd'	=> "{$dir_inst}/bin/searchd",
				'sphinx_installed' 	=> 'true',
				'sphinx_path' 		=> $dir_inst
				);
		//update admin options
		$this->config->update_admin_options($admin_options);			
	}else {
            return array('err' => 'Installation: Installation completed, but configuration files not found.<br/>
                Check installation directory: '.$dir_inst);
	}
		
	//////////////////
     	//rewrite pre defined 
     	//variables in config 
     	//file to their values  
     	//////////////////
     	
	//rewrite pre defined variables in config file  
	$res = $this->rewrite_config_variables($this->config->admin_options['sphinx_conf']);	
	if ( is_array($res) ){
            return $res;
	}
			
	$res = $this->setup_sphinx_counter_tables();
        if (is_array($res)){
            return $res;
        }

        $this->setup_cron_job();
		
	//////////////////
	//run re indexing 
	//////////////////
	/*$ssb = new SphinxSearch_Backend($this->config);
	$res = $ssb->reindex_sphinx();
	if ( is_array($res) ){
            return $res;
	}
	*/
	return true;
     }

     function setup_cron_job()
     {
         $search = array(
                '{path_to_sphinx}' => $this->config->admin_options['sphinx_path'],
     		'{path_to_indexer}' => $this->config->admin_options['sphinx_indexer'],
     		'{path_to_config}'   => $this->config->admin_options['sphinx_conf'],
     		'{index_prefix}' => $this->config->admin_options['sphinx_index'],
     		'{searchd_port}' => $this->config->admin_options['sphinx_port']
     	);
        $delta_template = file_get_contents(SPHINXSEARCH_PLUGIN_DIR.
                '/rep/cron_reindex_delta.php.tpl');
        $main_template = file_get_contents(SPHINXSEARCH_PLUGIN_DIR.
                '/rep/cron_reindex_main.php.tpl');
        $stats_template = file_get_contents(SPHINXSEARCH_PLUGIN_DIR.
                '/rep/cron_reindex_stats.php.tpl');
     	$delta_rewrited = str_replace(array_keys($search), $search, $delta_template);
        $main_rewrited = str_replace(array_keys($search), $search, $main_template);
        $stats_rewrited = str_replace(array_keys($search), $search, $stats_template);
        $cron_dir = $this->config->admin_options['sphinx_path'].'/cron';
        if (!file_exists($cron_dir)){            
            if ( ! mkdir($cron_dir) ){
                return array('err' => "Installation: Can not create directory ". $cron_dir .
                        " check the permissions.");
            }
        } else {
            if (!is_writable($cron_dir)){
                return array('err' => "Installation: ". $cron_dir ." is not writeable, ".
                        " check the permissions.");
            }
        }
        $delta_filename = $cron_dir.'/cron_reindex_delta.php';
        $main_filename = $cron_dir.'/cron_reindex_main.php';
        $stats_filename = $cron_dir.'/cron_reindex_stats.php';
        if ( ! file_put_contents($delta_filename, $delta_rewrited) ){
            return array('err' => "Installation: Can not write to file ".
                        $delta_filename ." check the permissions.");
        }
        if ( ! file_put_contents($main_filename, $main_rewrited) ){
            return array('err' => "Installation: Can not write to file ".
                        $main_filename ." check the permissions.");
        }
        if ( ! file_put_contents($stats_filename, $stats_rewrited) ){
            return array('err' => "Installation: Can not write to file ".
                        $stats_filename ." check the permissions.");
        }
        return true;
     }

     function setup_sphinx_counter_tables()
     {
         global $table_prefix, $wpdb;

         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         //////////////////
	//Create sph_counter
	//table
	//////////////////

	$sql = "CREATE TABLE IF NOT EXISTS {$table_prefix}sph_counter (
               counter_id int(11) NOT NULL,
               max_doc_id int(11) NOT NULL,
               PRIMARY KEY  (counter_id)
             );";

        dbDelta($sql);
	
	//////////////////
	//Create sph_stats
	//table
	//////////////////
	$sql = "CREATE TABLE IF NOT EXISTS  `".$table_prefix."sph_stats`(
				`id` int(11) unsigned NOT NULL auto_increment,
				`keywords` varchar(255) NOT NULL default '',
				`date_added` datetime NOT NULL default '0000-00-00 00:00:00',
				`keywords_full` varchar(255) NOT NULL default '',
                                `status` tinyint(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (`id`),
				KEY `keywords` (`keywords`),
				FULLTEXT `ft_keywords` (`keywords`)
				) ENGINE=MyISAM;";
	dbDelta($sql);
        return true;
     }
}
