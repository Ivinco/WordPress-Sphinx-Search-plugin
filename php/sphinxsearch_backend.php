<?php
/*
	Copyright 2008  &copy; Ivinco Inc  (email : office@ivinco.com)

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

class SphinxSearch_Backend {
	/**
	 * Config object
	 */
	var $config = '';
	
	/**
	 * SphinxSearch_Backend Constructor
	 *
	 * @param SphinxSearch_Config $config
	 * @return SphinxSearch_Backend
	 */
	function SphinxSearch_Backend($config)
	{
		$this->config = $config;
	}
	
      /**
      * Draw admin page
      *
      */
	function print_admin_page() 
	{
            if (!current_user_can('manage_options'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
            $options = $this->config->get_admin_options();
            if (!empty($_POST['run_wizard'])){
                $options['wizard_done'] = 'false';
                $this->config->update_admin_options($options);
            }
            if ('false' == $options['wizard_done'] || !empty($_POST['run_wizard'])){
                $wizard_step = !empty($_POST['wizard_step']) ? $_POST['wizard_step'] : 1;
                
                if (!empty($_POST['wizard_process'])){
                    $wizard_step = $this->_process_wizard($wizard_step);
                    if (!$wizard_step){
                        return;
                    }
                }
                $options = $this->config->get_admin_options();
                if ('false' == $options['wizard_done']){
                    return $this->_setup_wizard($wizard_step);
                }
            }
            

            if (!empty($_POST['reindex_sphinx'])){
                $res = $this->reindex_sphinx();
		$success_message = 'Sphinx index successfully rebuilt.';			
            }elseif (!empty($_POST['clean_indexes'])){
			$res = $this->clean_index();
			$success_message = 'Index successfully purged! Try to reindex and start Sphinx search daemon again.';
		}elseif (!empty($_POST['start_sphinx'])){
			$res = $this->run_sphinx(true);
			$success_message = 'Sphinx successfully started.';
		}elseif (!empty($_POST['stop_sphinx'])){
			$res = $this->run_sphinx(false);
			$success_message = 'Sphinx successfully stopped.';
		}elseif (isset($_POST['update_SphinxSearchSettings'])) {
			$this->update_options();
			$success_message = 'Settings updated.';
		}
		if (is_array($res)){
				$error_message = $res['err'];
		}
		if (!empty($success_message) || !empty($error_message) ){
			require_once (SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_admin_messages.htm');
		}
		
		$devOptions = $this->config->get_admin_options(); //update options
		
		//load admin panel template		
		require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_admin_panel.htm');
	}

        private function _process_wizard($wizard_step)
        {
            if (1 == $wizard_step){
                
                if (empty($_POST['sphinx_host']) ||
                    empty($_POST['sphinx_port']) ||
                    empty($_POST['sphinx_index'])){
                    $error_message = 'Connection parameters can\'t be empty';
                    require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_admin_messages.htm');
                    require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_sphinx_connection.phtml');
                    return false;
                } else {
                    $this->_setup_sphinx_connect();
                }
            }

            if (2 == $wizard_step){
                if (!empty($_POST['install_sphinx'])){
                    $this->run_sphinx(false);
			$ssi = new SphinxSearch_Install($this->config);
			$res = $ssi->install();
                        $success_message = '';
                        if (true === $res){
                            $success_message = 'Sphinx successfully installed.';
                        } else {
                            $success_message = $res['err'];
                        }
                        $options['wizard_done'] = 'true';
                        $this->config->update_admin_options($options);
			require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_installation_process.htm');
                        return false;
                } else {
                    if (empty($_POST['detected_searchd']) || empty($_POST['detected_indexer'])){
                        $error_message = 'Path to searchd and indexer files can\'t be empty';
                        $sphinx_detect_searchd = $_POST['detected_searchd'];
                        $sphinx_detect_indexer = $_POST['detected_indexer'];
                        $sphinx_install_path = SPHINXSEARCH_SPHINX_INSTALL_DIR;
                        require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_admin_messages.htm');
                        require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_detect_sphinx.phtml');
                        return false;
                    } else {
                        $this->_setup_detected_sphinx();;
                    }
                }
            }

            if ( 3 == $wizard_step ){
                $sphinx_install_path = $_POST['sphinx_path'];
                if (empty($sphinx_install_path)) {
                    $error_message = 'Sphinx path can\'t be empty';
                }
                if (!file_exists($sphinx_install_path)){
                    @mkdir($sphinx_install_path);
                }
                if (!file_exists($sphinx_install_path)){
                    $error_message = 'Sphinx path '.$sphinx_install_path.' isn\'t exists!';
                } else if (!is_writable($sphinx_install_path)){
                    $error_message = 'Sphinx path '.$sphinx_install_path.' isn\'t writeable!';
                } else {
                    $this->_setup_sphinx_path();
                    $config_file_name = $this->_generate_config_file_name();
                    $config_file_content = $this->_generate_config_file_content();
                    $this->_save_config($config_file_name, $config_file_content);

                    mkdir($sphinx_install_path.'/var');
                    mkdir($sphinx_install_path.'/var/data');
                    mkdir($sphinx_install_path.'/var/log');

                    return $wizard_step+1;
                }

                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_admin_messages.htm');
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_sphinx_sphinx_config.phtml');
                return false;
            }
            if ( 4 == $wizard_step ){
                 $res = $this->reindex_sphinx();
                 $indexsation_done = $res;
                 require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_sphinx_indexsation.phtml');
                 return false;
             }

             if ( 5 == $wizard_step ){
                 $options = $this->config->get_admin_options();
                 $options['wizard_done'] = 'true';
                 $this->config->update_admin_options($options);

                 return true;
             }
            return $wizard_step+1;
        }

        private function _setup_wizard($wizard_step)
        {
            $options = $this->config->get_admin_options();

            if ( 1 == $wizard_step ){
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_sphinx_connection.phtml');
                return ;
            }

            if ( 2 == $wizard_step ){
                $sphinx_detect_searchd = $this->_detect_program('searchd');
                $sphinx_detect_indexer = $this->_detect_program('indexer');
                $sphinx_install_path = SPHINXSEARCH_SPHINX_INSTALL_DIR;
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_detect_sphinx.phtml');
                return ;
            }

            if ( 3 == $wizard_step ){
                $sphinx_install_path = SPHINXSEARCH_SPHINX_INSTALL_DIR;
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_detect_sphinx_config.phtml');
                return ;
            }

            if ( 4 == $wizard_step ){
                $options = $this->config->get_admin_options();
                $indexsation_done = false;
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_sphinx_indexsation.phtml');
                return ;
            }

            if ( 5 == $wizard_step ){
                $options = $this->config->get_admin_options();
                $config_content = $this->_generate_config_file_content();
                require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/admin_wizard_done.phtml');
                return ;
            }
            
        }

     private function _generate_config_file_name()
     {
         $options = $this->config->get_admin_options();
         $filename = $options['sphinx_path'].'/sphinx.conf';
         file_put_contents($filename, '');
         $options['sphinx_conf'] = $filename;
         $this->config->update_admin_options($options);
         return $filename;
     }

     private function _generate_config_file_content()
     {         
         $config_tempate = file_get_contents(SPHINXSEARCH_PLUGIN_DIR.'/rep/sphinx.conf');

         $sphinxInst = new SphinxSearch_Install($this->config);
         $content = $sphinxInst->generate_config_content($config_tempate);

         return $content;
     }

     private function _save_config($filename, $content)
     {
         file_put_contents($filename, $content);
     }



     private function _setup_sphinx_connect()
     {
         $this->config->admin_options['sphinx_host'] = $_POST['sphinx_host'];
         $this->config->admin_options['sphinx_port'] = $_POST['sphinx_port'];
         $this->config->admin_options['sphinx_index'] = $_POST['sphinx_index'];
         $this->config->update_admin_options();
         return true;
     }

     private function _setup_sphinx_path()
     {
         $this->config->admin_options['sphinx_path'] = $_POST['sphinx_path'];
         $this->config->update_admin_options();
         return true;

     }
     private function _setup_detected_sphinx()
     {
         $this->config->admin_options['sphinx_searchd'] = $_POST['detected_searchd'];
         $this->config->admin_options['sphinx_indexer'] = $_POST['detected_indexer'];
         $this->config->update_admin_options();
         return true;
     }

     private function _detect_program($progname)
     {
         $progname = escapeshellcmd($progname);
         $res = exec("whereis {$progname}");
         if (!preg_match("#{$progname}:\s?([\w/]+)#", $res, $matches)) {
            return false;
         }
         return $matches[1];
     }

     /**
	 * Update Options
	 *
	 */
	function update_options()
	{
		//get options array
		$devOptions = $this->config->admin_options;

		/**
		 * search_comments - search in comments
		 * search_posts - search in posts
		 * search_pages - search in pages
		 */
		foreach(array('search_comments', 'search_posts', 'search_pages') as $option){
			if (!empty($_POST[$option])) $devOptions[$option] = 'true';
			else $devOptions[$option] = 'false';
		}

		/**
		 * sphinx_conf - path to sphinx conf file
		 * sphinx_indexer - path to sphinx indexer file
		 * sphinx_searchd - path to sphinx search daemon file
		 * sphinx_index - name of main sphinx index in sphinx.conf
		 * stript_tags - user defined strip tags or keywords
		 * before_comment - keyword before Comment title
		 * before_page - keyword before Page title
		 * before_post - keyword before Post title
		 */
		foreach(array('sphinx_conf', 'sphinx_indexer', 'sphinx_searchd', 'sphinx_path',
					  'sphinx_index', 'strip_tags', 'censor_words') as $option){
							if (isset($_POST[$option])) $devOptions[$option] = trim($_POST[$option]);
					  }

		/**
		 * excerpt_before_match - tag before search keyword in content part
		 * excerpt_after_match - tag after search keyword in content part
		 * excerpt_before_match_title - tag before search keyword in title part
		 * excerpt_after_match_title - tag after search keyword in title part
		 * excerpt_chunk_separator - separator of content around the search keyword
		 */
		foreach(array('excerpt_before_match', 'excerpt_after_match', 'excerpt_before_match_title',
					  'excerpt_after_match_title', 'excerpt_chunk_separator', 'before_comment',
					  'before_page', 'before_post') as $option){
							if (isset($_POST[$option]))  $devOptions[$option] = $_POST[$option];
					  }

		/**
		 * excerpt_limit - limit number of characters in excerpt
		 * excerpt_around - limit number of words in excerpt around the search keyword
		 * sphinx_port - sphinx search connection port
		 */
		foreach(array('excerpt_limit', 'excerpt_around', 'sphinx_port') as $option){
			if (isset($_POST[$option]))  $devOptions[$option] = $_POST[$option];
		}

		//recognize sphinx paths and set sphinx as installed
		//sphinx_path - path to sphinx rep
		//sphinx_installed - signal of installed sphinx rep or not
		if (file_exists($devOptions['sphinx_searchd']) &&
			file_exists($devOptions['sphinx_conf']) &&
			file_exists($devOptions['sphinx_indexer'])){
			$devOptions['sphinx_installed'] = 'true';
			if (dirname($devOptions['sphinx_searchd']) == dirname($devOptions['sphinx_conf'])){
				$devOptions['sphinx_path'] = dirname($devOptions['sphinx_searchd']);
			}elseif (dirname(dirname($devOptions['sphinx_searchd'])) == dirname(dirname($devOptions['sphinx_conf']))) {
				$devOptions['sphinx_path'] = dirname(dirname($devOptions['sphinx_searchd']));
			}else {
				$devOptions['sphinx_path'] = dirname($devOptions['sphinx_searchd']);
			}
		}

		$this->config->update_admin_options($devOptions);
	}

	 /**
	 * Run Sphinx indexer to reindex content
	 *
	 * @return bool
	 */
    function reindex_sphinx()
    {
     	if (!file_exists($this->config->admin_options['sphinx_searchd']) ||
			!file_exists($this->config->admin_options['sphinx_conf']) ||
			!file_exists($this->config->admin_options['sphinx_indexer'])){
			return  array('err' =>'Indexer: configuration files not found.');
		}elseif (empty($this->config->admin_options['sphinx_index'])){
			return  array('err' =>'Indexer: Sphinx index name not specified.');
		}else {
			if ($this->config->check_sphinx_running()) {
				$rotate = '--rotate ';
			}else {
				$rotate = '';
			}
			//re index all indexes with restart searchd
			$command = "{$this->config->admin_options['sphinx_indexer']} --config {$this->config->admin_options['sphinx_conf']} {$this->config->admin_options['sphinx_index']}delta {$this->config->admin_options['sphinx_index']}main $rotate ";
			exec($command, $output);
			//echo implode("<br/>", $output);
			if (preg_match("#ERROR:#", implode(" ", $output))){
				return  array('err' =>'Indexer: reindexing error, try to run it manually.' .'<br/>Command: ' . $command);
			}
		}
		$this->config->need_reindex(false);
		$this->config->update_admin_options();
		return true;
     }

     /**
      * Start and stop Sphinx search daemon, true - it is start and false - it is stop
      *
      * @param boolean $start
      * @return boolean
      */
     function run_sphinx($start)
     {
     	//run Sphinx search daemon
     	if (true === $start){
     		$this->run_sphinx(false); //kill daemon if runned
     		$command = "{$this->config->admin_options['sphinx_searchd']} --config {$this->config->admin_options['sphinx_conf']} ";
     		exec($command, $output);
     		//echo implode("<br/>", $output);
     		$this->config->admin_options['sphinx_running'] = 'true';
     	}else {
     		//stop Sphinx search daemon
     		if ($this->config->check_sphinx_running()) {
                    $pid = $this->config->get_sphinx_pid();
     			$command = "kill -TERM $pid";
     			exec($command, $output);
     			//echo implode("<br/>", $output);
     		}
     		$this->config->admin_options['sphinx_running'] = 'false';
     	}

     	$this->config->update_admin_options();
     	return true;
     }
}
