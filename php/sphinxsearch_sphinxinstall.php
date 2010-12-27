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
                        ' do not exist or not available.');
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

     public function generate_config_content($template_content)
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
                '{path_to_php}' => $wizard->detectProgram('php'),
                '{path_to_wp_config_php}' => dirname(dirname(dirname($this->plugin_sphinx_dir)))
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
                        " isn't exists, ".
                        " create the upload directory of you WordPress.");
        }

        if (!is_writable($parentInstDir)){
            return array('err' => "Installation: Directory ". $parentInstDir .
                        " isn't writeable, ".
                        " check the permissions.");
        }

        if (!file_exists($dir_inst)){
            if ( !mkdir($dir_inst) ){
                return array('err' => "Installation: Can't create directory ". $dir_inst .
                        " check the permissions.");
            }
        } else {
            if (!is_writable($dir_inst)){
                return array('err' => "Installation: ". $dir_inst ." isn't writeable, ".
                        " check the permissions.");
            }
            //clear previouse installations
            exec("rm -fr " . $dir_inst.'/*');
        }

	//////////////////
     	//Copy Source Filename
     	//to destionation dir
     	//////////////////
     	
	$res = copy($sphinx_source.$this->latest_sphinx_filename, $dir_inst.'/'.$this->latest_sphinx_filename);
	if ($res == false) {
            return array('err' => "Installation: Can't copy ".
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
                        'Command: '.$openarch);
                    
            $dir_rep = str_replace('.tar.gz', '', $this->latest_sphinx_filename);
            chdir($dir_inst.'/'.$dir_rep);
		
	//////////////////
     	//Run:
     	//./configure
     	//make & make install
     	//////////////////

	//echo "<pre>";
            //configure
            $command = "./configure --with-mysql --prefix=$dir_inst 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Configure error, please refer to Sphinx documentation about installation requirements, fix the problem and try again.';
                //echo '<script>alert("'.$msg.'")</script>';
                return  array('err' => $msg.'<br/>Command: '.$command." at the dir:".$dir_inst.'/'.$dir_rep);
            }

            flush();

            //making
            $command = "make 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Make error, please refer to Sphinx documentation about installation requirements, fix the problem and try again.';
                //echo '<script>alert("'.$msg.'")</script>';
                return  array('err' => $msg.'<br/>Command: '.$command);
            }

            flush();

            //make install
            $command = "make install 2>&1";
            exec($command, $output, $retval);
            if (0 != $retval)
            {
                $msg = 'Installation: Make install error, try to run it manually or fix a problem and try again!';
                //echo '<script>alert("'.$msg.'")</script>';
                return array('err' => $msg.'<br/>Command: '.$command);
            }

            //echo "</pre>";
            flush();

            if (!file_exists($dir_inst.'/bin/indexer') || !file_exists($dir_inst.'/bin/searchd')){
                $msg = "Installation: indexer ({$dir_inst}/bin/indexer) or search deamon ({$dir_inst}/bin/searchd) was not found.";
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
	    return array('err' => "Installation: Can't copy ".$this->plugin_sphinx_dir.'/rep/sphinx.conf'." to ".
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
                See installation directory: '.$dir_inst);
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
			
	$res = $this->setupSphinxCounterTables();
        if (is_array($res)){
            return $res;
        }
		
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

     public function setupSphinxCounterTables()
     {
         global $table_prefix, $wpdb;
         //////////////////
	//Create sph_counter
	//table
	//////////////////

	$sql = "CREATE TABLE IF NOT EXISTS {$table_prefix}sph_counter (
               counter_id int(11) NOT NULL,
               max_doc_id int(11) NOT NULL,
               PRIMARY KEY  (counter_id)
             );";
	$res = $wpdb->query($sql);
	if (false === $res){
            return array('err' => "Can\'t create table {$table_prefix}sph_counter .<br/>"."Command: ".$wpdb->last_query );
	}
	//////////////////
	//Create sph_stats
	//table
	//////////////////
	$sql = "CREATE TABLE IF NOT EXISTS  `".$table_prefix."sph_stats`(
				`id` int(11) unsigned NOT NULL auto_increment,
				`keywords` varchar(255) NOT NULL default '',
				`date_added` datetime NOT NULL default '0000-00-00 00:00:00',
				`keywords_full` varchar(255) NOT NULL default '',
				PRIMARY KEY  (`id`),
				KEY `keywords` (`keywords`),
				FULLTEXT `ft_keywords` (`keywords`)
				) ENGINE=MyISAM;";
	$res = $wpdb->query($sql);
	if (false === $res){
            return array('err' => "Can\'t create table {$table_prefix}sph_stats .<br/>"."Command: ".$wpdb->last_query );
	}
        return true;
     }
}
