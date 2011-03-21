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

class SphinxSearch_Backend {
	/**
	 * Config object
	 */
	var $config = '';

        /**
         * View object
         */
        var $view = null;

	/**
	 * SphinxSearch_Backend Constructor
	 *
	 * @param SphinxSearch_Config $config
	 * @return SphinxSearch_Backend
	 */
	function SphinxSearch_Backend($config)
	{
		$this->config = $config;

                $this->view = $config->get_view();

                if (!empty($_GET['menu']) && !empty ($_REQUEST['action'])){
                    if ('terms_editor' == $_GET['menu'] && $_REQUEST['action'] == 'export'){
                        $terms_editor = new TermsEditorController($this->config);
                        $terms_editor->_export_keywords();
                    }
                }
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

            $wizard = new WizardController($this->config);
            if (!empty($_POST['start_wizard']) ||
                    (empty($options['sphinx_conf']) &&
                        'false' == $options['wizard_done'])){
                $this->view->menu = 'wizard';
                $wizard->start_action();
            }

            

            if (!empty($_GET['menu'])){
                switch($_GET['menu']){
                    case 'terms_editor':
                        $terms_editor = new TermsEditorController($this->config);
                        $terms_editor->index_action();
                        $this->view->menu = 'terms_editor';
                        //return;
                        break;
                    case 'stats':
                        $stats = new StatsController($this->config);
                        $stats->index_action();
                        $this->view->menu = 'stats';
                        //return;
                        break;
                    case 'search_settings':
                        $this->view->menu = 'search_settings';
                        break;
                }

                
            }

            $sphinxService = new SphinxService($this->config);
            $res = false;
            $error_message = $success_message = '';
            if (!empty($_POST['reindex_sphinx'])){
                $res = $sphinxService->reindex();
                $success_message = 'Sphinx successfully reindexed.';
            }else if (!empty($_POST['start_sphinx'])){
                $res = $sphinxService->start();
		$success_message = 'Sphinx successfully started.';
            }elseif (!empty($_POST['stop_sphinx'])){
		$res = $sphinxService->stop();
		$success_message = 'Sphinx successfully stopped.';
            }elseif (isset($_POST['update_SphinxSearchSettings'])) {
                $this->update_options();
                $success_message = 'Settings updated.';
            }
                        
            if (is_array($res)){
                $error_message = $res['err'];
            }

            
            $this->view->assign('index_modify_time', $sphinxService->get_index_modify_time());

            if (!empty($error_message)){
                $this->view->assign('error_message', $error_message);
            }
            if (!empty($success_message)){
                $this->view->assign('success_message', $success_message);
            }
		
            $devOptions = $this->config->get_admin_options(); //update options
            $this->view->assign('devOptions', $devOptions);
            //load admin panel template
            $this->view->assign('header', 'Sphinx Search for Wordpress');

            if ('true' != $devOptions['check_stats_table_column_status']) {
                global $table_prefix;
                $this->view->assign('error_message',  "{$table_prefix}sph_stats table required an update.<br>
                Please run the following command in MySQL client to update the table: <br>
                alter table {$table_prefix}sph_stats add `status` tinyint(1) NOT NULL DEFAULT '0';
                <br><br>
                This update will allow to use Sphinx Search for Top/Related and Latest search terms widgets!");
            }

            $this->view->render('admin/layout.phtml');
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
					  'strip_tags', 'censor_words') as $option){
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
		foreach(array('excerpt_limit', 'excerpt_around') as $option){
			if (isset($_POST[$option]))  $devOptions[$option] = $_POST[$option];
		}

		//recognize sphinx paths and set sphinx as installed
		//sphinx_path - path to sphinx rep
		//sphinx_installed - signal of installed sphinx rep or not
		if (file_exists($devOptions['sphinx_searchd']) &&
			file_exists($devOptions['sphinx_conf']) &&
			file_exists($devOptions['sphinx_indexer']) &&
                        'false' == $devOptions['sphinx_installed']){
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

	

    
}
