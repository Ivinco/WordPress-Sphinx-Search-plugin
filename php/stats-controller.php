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

        $this->_wpdb = $wpdb;
        $this->_table_prefix = $table_prefix;
        $this->view = new SphinxView();
        $this->_config = $config;
        $this->view->assign('header', 'Sphinx Search :: Statistics');
    }

    function index_action()
    {
        if (!empty($_POST) && (!empty($_POST['doaction']) || !empty($_POST['doaction2']) )){
            $action = !empty($_POST['doaction']) ? $_POST['action'] : $_POST['action2'];
            switch($action){
                case 'approve':
                    $this->_approveKeywords($_POST['keywords']);
                    break;
                case 'ban':
                    $this->_banKeywords($_POST['keywords']);
                    break;
            }

        }
        
        if ( isset( $_GET['apage'] ) ){
            $page = abs( (int) $_GET['apage'] );
        } else {
            $page = 1;
        }
        $tab = !empty($_GET['tab'])? $_GET['tab'] : 'new';

        $this->_get_new_keywords($page, $tab);
        //run after get keywords list
        $this->_build_pagination($page);

        $this->view->period = !empty($_REQUEST['period']) ? $_REQUEST['period'] : 7;
        $this->view->tab = $tab;
        $this->view->plugin_url = $this->_config->get_plugin_url();
        $this->view->render('admin/stats/layout.phtml');
    }

    function _approveKeywords($keywords)
    {
        foreach($keywords as $keyword){
            $sql = "update ".$this->_table_prefix."sph_stats set status = 1
            where keywords_full = '".$this->_wpdb->escape($keyword)."'";
            $this->_wpdb->query($sql);
        }

    }

    function _banKeywords($keywords)
    {
        foreach($keywords as $keyword){
            $sql = "update ".$this->_table_prefix."sph_stats set status = 2
            where keywords_full = '".$this->_wpdb->escape($keyword)."'";
            $this->_wpdb->query($sql);
        }
    }

    function _build_pagination($page)
    {
        $sql_found_rows = 'SELECT FOUND_ROWS()';
        $total = $this->_wpdb->get_var($sql_found_rows);

        $page_links = paginate_links( array(
            'base' => add_query_arg( 'apage', '%#%' ),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => ceil($total / $this->_keywords_per_page),
            'current' => $page
        ));        
        
        $this->view->page_links = $page_links;
        $this->view->page = $page;        
        $this->view->total = $total;
        $this->view->keywords_per_page = $this->_keywords_per_page;
    }

    function _get_new_keywords($page, $status)
    {
        switch (strtolower($_REQUEST['period'])) {

            case '14':
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
            case '30':
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
            case '90':
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
            case '180':
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
            case '365':
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
            case '-1':
                $sqlPeriod = '';
                break;
            case '7':
            default:
                $sqlPeriod = " and date_added > date_sub(now(), interval {$_REQUEST['period']} day) ";
                break;
        }

        switch (strtolower($_REQUEST['sort_by'])) {
            case 'key':
                $sort_by = 'keywords';
                break;
            case 'date':
                $sort_by = 'date_added';
                break;
            case 'cnt':
            default:
                $sort_by = 'cnt';
                break;
        }
        $sort_order = strtolower($_REQUEST['sort_order']) == 'asc' ? 'asc' : 'desc';
        switch ($status) {            
            case 'approved':
                $istatus = 1;
                break;
            case 'ban':
                $istatus = 2;
                break;
            case 'new':
            default:
                $istatus = 0;
                break;
        }
        $start = ( $page - 1 ) * $this->_keywords_per_page;

        $sql = 'select SQL_CALC_FOUND_ROWS id, keywords, keywords_full, 
                    max(date_added) as date_added, count(1) as cnt
            from '.$this->_table_prefix.'sph_stats
            where status = '.$istatus.'
                '.$sqlPeriod.'
            group by keywords
            order by '.$sort_by.' '.$sort_order.'
            limit '.$start.', '.$this->_keywords_per_page;
        $this->view->keywords = $this->_wpdb->get_results($sql, OBJECT);
        $this->view->start = $start;
    }
}