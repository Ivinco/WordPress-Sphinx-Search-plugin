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

class StatsController
{
    /**
     * Special object to get/set plugin configuration parameters
     * @access private
     * @var SphinxSearch_Config
     *
     */
    var $_config = null;

    /**
     * Special object used for template system
     * @access private
     * @var SphinxView
     *
     */
    var $view = null;

    var $_sphinx = null;
    var $_results = array();

    var $_wpdb = null;
    var $_table_prefix = null;
    var $_keywords_per_page = 50;

    function  StatsController(SphinxSearch_Config $config)
    {
        $this->__construct($config);        
    }

    function  __construct(SphinxSearch_Config $config)
    {
        global $wpdb, $table_prefix;

        $this->_sphinx = $config->init_sphinx();
        $this->_wpdb = $wpdb;
        $this->_table_prefix = $table_prefix;
        $this->view = $config->get_view();
        $this->_config = $config;
        $this->view->assign('header', 'Sphinx Search :: Statistics');
    }

    function index_action()
    {
        if ( isset( $_POST['apage'] ) ){
            $page = abs( (int) $_POST['apage'] );
        } else if ( isset( $_GET['apage'] ) ){
            $page = abs( (int) $_GET['apage'] );
        } else {
            $page = 1;
        }
        $tab = !empty($_GET['tab'])? $_GET['tab'] : 'stats';

        $period_param = !empty($_REQUEST['period']) ? intval($_REQUEST['period']) : 7;

        $keywords = $this->_get_stat_keywords($page, $period_param);
        //run after get keywords list
        $page_links = $this->_build_pagination($page);

        $this->view->keywords = $keywords;
        $this->view->start = ( $page - 1 ) * $this->_keywords_per_page;
        $this->view->sterm = !empty($_REQUEST['sterm']) ? stripslashes($_REQUEST['sterm']) : '';
        $this->view->period = $period_param;

        $this->view->page_links = $page_links;
        $this->view->page = $page;
        $this->view->total = !empty($this->_results['total_found'])?$this->_results['total_found']:0;
        $this->view->keywords_per_page = $this->_keywords_per_page;
        
        $this->view->tab = $tab;
        $this->view->plugin_url = $this->_config->get_plugin_url();


    }

    function _build_pagination($page)
    {
        if (empty($this->_results)){
            return;
        }
        //$sql_found_rows = 'SELECT FOUND_ROWS()';
        //$total = $this->_wpdb->get_var($sql_found_rows);

        $total = $this->_results['total_found'];

        $pagination = array(
            'base' => add_query_arg( 'apage', '%#%' ),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => ceil($total / $this->_keywords_per_page),
            'current' => $page
        );

        if (!empty($_REQUEST['period'])){
            $pagination['add_args']['period'] = $_REQUEST['period'];
        }
        if (!empty($_REQUEST['sort_order'])){
            $pagination['add_args']['sort_order'] = $_REQUEST['sort_order'];
        }
        if (!empty($_REQUEST['sort_by'])){
            $pagination['add_args']['sort_by'] = $_REQUEST['sort_by'];
        }     

        $page_links = paginate_links( $pagination );
        
        return $page_links;
    }

    function _get_stat_keywords($page, $period_param)
    {
        $start = ( $page - 1 ) * $this->_keywords_per_page;

        if ($period_param > 0) {
            $this->_sphinx->SetFilterRange("date_added", strtotime("-{$period_param} days"), time());
        }

        $sort_order = 'asc';
        if(!empty($_REQUEST['sort_order']) && strtolower($_REQUEST['sort_order']) == 'desc'){
            $sort_order = 'desc';
        }

        $sort_by_param = !empty($_REQUEST['sort_by']) ? $_REQUEST['sort_by'] : 'cnt';
        switch (strtolower($sort_by_param)) {
            case 'date':
                $sort_by = 'date_added';
                break;
            case 'cnt':
            default:
                $sort_by = '@count';
                break;
        }

        $this->_sphinx->SetGroupBy ( "keywords_crc", SPH_GROUPBY_ATTR, "$sort_by $sort_order" );
        if ('asc' == $sort_order){
            $this->_sphinx->SetSortMode(SPH_SORT_ATTR_ASC, "date_added");
        } else {
            $this->_sphinx->SetSortMode(SPH_SORT_ATTR_DESC, "date_added");
        }
        $this->_sphinx->SetLimits($start, $this->_keywords_per_page);

        $res = $this->_sphinx->Query("",$this->_config->get_option('sphinx_index').'stats');

        if (empty($res['matches'])){
            return array();
        }
        $this->_results = $res;
        $ids = array_keys($res['matches']);

        $sql = 'select id, keywords,  date_added
            from '.$this->_table_prefix.'sph_stats
            where id in ('.  implode(',', $ids).')
            order by FIELD(id, '.  implode(',', $ids).')';

        $keywords = $this->_wpdb->get_results($sql, OBJECT_K);
        
        foreach($res['matches'] as $index => $match){
            $keywords[$index]->cnt = $match['attrs']['@count'];
        }
        return $keywords;
    }

}