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

class SphinxSearch_FrontEnd
{
	/**
	 * Sphinx Search Results
	 */
	var $search_results = '';
	
	/**
	 * Posts info returned by Sphinx
	 *
	 * @var array
	 */
	var $posts_info = array();
	
	/**
	 * Total posts found
	 *
	 * @var int
	 */
	var $post_count = 0;
	
	/**
	 *  Search keyword
	 *
	 * @var string
	 */
	var $search_string = '';
        var $search_string_original = '';
	
	/**
	 * Search params
	 */
	var $params = array();
	
	/**
	 * Config object
	 */
	var $config = '';
	
	/**
	 * IS searchd running
	 */
	var $is_searchd_up = true;
        
	var $top_ten_is_related = false;
	
	/**
	 * IS search mode MATCH ANY 
	 *
	 * @var boolean
	 */
	var $used_match_any = false;
	
	/**
	 * Post/Pages/Comments count variables
	 */
	var $posts_count = 0;
	var $pages_count = 0;
	var $comments_count = 0;

        /**
         *
         * 
         */
        var $_top_ten_total = 0;
	
	/**
	 * Delegate config object from SphinxSearch_Config class
	 * get search keyword from GET parameters
	 *
	 * @param SphinxSearch_Config $config
	 * @return SphinxSearch_FrontEnd
	 */
	function SphinxSearch_FrontEnd($config)
	{
		global $wpdb;
		
		//initialize config
		$this->config = $config;
				
		if (!isset($_GET['search_comments']) && !isset($_GET['search_posts']) && !isset($_GET['search_pages'])){
			$this->params['search_comments'] = $this->config->admin_options['search_comments']=='false'?'':'true';
			$this->params['search_posts'] = $this->config->admin_options['search_posts']=='false'?'':'true';
			$this->params['search_pages'] = $this->config->admin_options['search_pages']=='false'?'':'true';
		}else{
			$this->params['search_comments'] = $wpdb->escape($_GET['search_comments']); 
			$this->params['search_posts'] = $wpdb->escape($_GET['search_posts']); 			
			$this->params['search_pages'] = $wpdb->escape($_GET['search_pages']); 
		}
		

		if (!empty($_GET['search_sortby'])){
			$this->params['search_sortby'] = $wpdb->escape($_GET['search_sortby']); 		
		}else {
			$this->params['search_sortby'] = '';//sort by relevance, by default
		}
	}
	
	/**
	 * Make Query to Sphinx search daemon and return result ids
	 *
	 * @return array
	 */
	function query($search_string)
	{ 
		global $wp_query;

                $this->search_string_original = $search_string;
                
                $this->search_string = $search_string;

                $sphinx = $this->config->init_sphinx();

		////////////
		// set filters
		////////////		

		if ( empty($this->params['search_comments']) ){
			$sphinx->SetFilter('isComment', array(0)); 
		}
				
		if ( empty($this->params['search_pages']) ){
			$sphinx->SetFilter('isPage', array(0));
		}
			
		if ( empty($this->params['search_posts']) ){
			$sphinx->SetFilter('isPost', array(0));
		}
		

		if ( $this->params['search_sortby'] == 'date' ){ {
                    $sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'date_added');}
		} else if ( $this->params['search_sortby'] == 'relevance' ){
                    $sphinx->SetSortMode(SPH_SORT_RELEVANCE);
		} else {
                    $sphinx->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'date_added');
		}

		////////////
		// set limits
		////////////

		$searchpage = ( !empty($wp_query->query_vars['paged']) )
                        ? $wp_query->query_vars['paged'] : 1;
		$posts_per_page = intval(get_settings('posts_per_page'));
		$offset = intval( ( $searchpage - 1 ) * $posts_per_page);
		$sphinx->SetLimits($offset, $posts_per_page, $this->config->admin_options['sphinx_max_matches']);
		
		////////////
		// do query
		////////////		
		
		//replace key-buffer to key buffer
		//replace key -buffer to key -buffer
		//replace key- buffer to key buffer
		//replace key - buffer to key buffer
		$this->search_string = $this->unify_keywords($this->search_string);                

		$res = $sphinx->Query ( $this->search_string, $this->config->admin_options['sphinx_index'] );

		if (empty($res["matches"]) && $this->is_simple_query($this->search_string)){
			$sphinx->SetMatchMode ( SPH_MATCH_ANY );
			$res = $sphinx->Query ( $this->search_string, $this->config->admin_options['sphinx_index'] );
			$this->used_match_any = true;
		}
                
		//to do something usefull with error
		if ( $res === false ){
			$error = $sphinx->getLastError();
			if (preg_match('/connection/', $error) and preg_match('/failed/', $error)) 
				$this->is_searchd_up = false;
			return array();
		}
		////////////
		// try match any and save search string
		////////////
		$partial_keyword_match_or_adult_keyword = false;
		if (  ( strtolower($this->search_string) !=
                        $this->clear_censor_keywords($this->search_string) ) ||
			$this->used_match_any === true){
			$partial_keyword_match_or_adult_keyword = true;
		}

		if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false){
			// make new query without filters
			if (!is_array($res["matches"])){
				$this->used_match_any = false;
				$sphinx->_filters = array();
				$sphinx->SetLimits(0, 1);
				$res_tmp = $sphinx->Query ( $this->search_string, $this->config->admin_options['sphinx_index'] );
				//to do something usefull with error
				if ( $res_tmp === false ){
					$error = $sphinx->getLastError();
					if (preg_match('/connection/', $error) and preg_match('/failed/', $error)) {
						$this->is_searchd_up = false;
					}
					return array();
				}
				if (is_array($res_tmp["matches"]) && $partial_keyword_match_or_adult_keyword === false) {
					$this->insert_sphinx_stats($this->search_string);
				}
			}elseif($partial_keyword_match_or_adult_keyword === false) {
				$this->insert_sphinx_stats($this->search_string);
			}
		}
                
		//if no posts found return empty array
		if (!is_array($res["matches"])) return array();
		
		//group results
		$sphinx->ResetFilters();
		$sphinx->SetGroupBy('post_type', SPH_GROUPBY_ATTR, "@count desc");
		$sphinx->SetLimits(0, 1000);	
		$res_tmp = $sphinx->Query ( $this->search_string, $this->config->admin_options['sphinx_index'] );
		if ($res_tmp['matches']){
			foreach ($res_tmp['matches'] as $m){
				switch ($m['attrs']['post_type']){
					case '0':
						$this->posts_count = $m['attrs']['@count'];
						break;
					case '1':
						$this->pages_count = $m['attrs']['@count'];
						break;
					case '2':
						$this->comments_count = $m['attrs']['@count'];
						break;
				}
			}
		}
		
		//save matches	
		$this->search_results = $res;

		return $this;
	}
	
	/**
	 * Is query simple, if yes we use match any mode if nothing found in extended mode
	 *
	 * @param string $query
	 * @return boolean
	 */
	function is_simple_query($query)
	{
		$stopWords = array('@title', '@body', '@category', '!', '-', '~', '(', ')', '|', '"', '/');
		foreach ($stopWords as $st){
			if (strpos($query, $st) !== false) return false;
		}
		return true;
	}
	
	/**
	 * Parse matches and collect posts ids and comments ids
	 *
	 */
	function parse_results()
	{
		global $wpdb;
		
		$content = array();
		foreach($this->search_results["matches"] as $key => $val){
			if ($val['attrs']['comment_id'] == 0)
				$content['posts'][] = array ( 'post_id' => ($key-1)/2, 'weight' => $val['weight'], 'comment_id' => 0, 'is_comment' => 0); 
			else 
				$content['posts'][] = array( 'comment_id' => ($key)/2, 'weight' => $val['weight'], 'post_id' => $val['attrs']['post_id'], 'is_comment' => 1);
		}

		$this->posts_info = $content['posts'];
		$this->post_count = $this->search_results['total_found'];
		
		return $this;
	}
	
	/**
	 * Make new posts based on our Sphinx Search Results
	 *
	 * @param object $posts
	 * @return object $posts
	 */
	function posts_results()
	{
		global $wpdb;
		////////////////////////////
		//fetching coments and posts data
		////////////////////////////
		
		$posts_ids = array();
		$comments_ids = array();
		foreach($this->posts_info as $p){
			if ($p['is_comment']){
				$comments_ids[] = $p['comment_id'];				
			}
			$posts_ids[] = $p['post_id'];			
		}
		$posts_data = array();
		if (!empty($posts_ids)){
			$query = "SELECT * FROM $wpdb->posts WHERE ID in (".implode(',',$posts_ids).")";
			$posts_data = $wpdb->get_results($query);
		}
		
		$comments_data = array();
		if (!empty($comments_ids)){
			$query = "SELECT * FROM $wpdb->comments WHERE comment_ID in (".implode(',',$comments_ids).")";
			$comments_data = $wpdb->get_results($query);
		}
		
		unset($posts_ids);
		unset($comments_ids);
		
		////////////////////////////
		//Make assoc array of 
		//posts and comments data
		////////////////////////////
		
		$posts_content = array();
		$posts_titles = array();
		$posts_data_assoc = array();
		$comments_content = array();
		foreach ($posts_data as $k => $p){
			//make id as indexes
			$posts_data_assoc[$p->ID] = $p;
			
			$posts_content[$p->ID] = $p->post_content;
			$posts_titles[$p->ID] = $p->post_title;
		}
		foreach ($comments_data as $c){
			$comments_content[$c->comment_ID] = $c->comment_content;
			$comments_content_data[$c->comment_ID]['comment_date'] = $c->comment_date;
			$comments_content_data[$c->comment_ID]['comment_date_gmt'] = $c->comment_date_gmt;
			$comments_content_data[$c->comment_ID]['comment_author'] = $c->comment_author;
		}

		unset($posts_data);
		unset($comments_data);
		
		////////////////////////////
		//excerpts of contents 
		//and titles
		////////////////////////////
		
		$posts_content_excerpt = $this->get_excerpt($posts_content); 
		$posts_titles_excerpt = $this->get_excerpt($posts_titles, true); 
		$comments_content_excerpt = $this->get_excerpt($comments_content); 
		//check if server is down
		if ( $posts_content_excerpt === false || $posts_titles_excerpt === false || $comments_content_excerpt === false )
			return null;
		
		unset($posts_content);
		unset($posts_titles);
		unset($comments_content);
		////////////////////////////
		//merge posts and comments 
		//excerpts into gloabl
		//posts array
		////////////////////////////

		$posts = array();		
		foreach($this->posts_info as $post){
			$posts_data_assoc_arry = array();
			$pID = $post['post_id'];
			if (is_object($posts_data_assoc[$pID])) {				
				$posts_data_assoc_arry[$pID] = get_object_vars($posts_data_assoc[$pID]);
			}                        
			//it is comment
			if ($post['is_comment'])  {
				$cID = $post['comment_id'];
				
				$posts_data_assoc_arry[$pID]['post_content'] = $comments_content_excerpt[$cID];
                                $posts_data_assoc_arry[$pID]['post_excerpt'] = $comments_content_excerpt[$cID];
				
				$posts_data_assoc_arry[$pID]['post_title'] = strip_tags($posts_titles_excerpt[$pID]);
				$posts_data_assoc_arry[$pID]['sphinx_post_title'] = $this->config->admin_options['before_comment'].$posts_titles_excerpt[$pID];
				$posts_data_assoc_arry[$pID]['comment_id'] = $cID;
				$posts_data_assoc_arry[$pID]['post_date_orig'] = $posts_data_assoc_arry[$pID]['post_date'];				
				$posts_data_assoc_arry[$pID]['post_date_gmt_orig'] = $posts_data_assoc_arry[$pID]['post_date_gmt'];
				$posts_data_assoc_arry[$pID]['post_date'] = $comments_content_data[$cID]['comment_date'];
				$posts_data_assoc_arry[$pID]['comment_author'] = $comments_content_data[$cID]['comment_author'];
				$posts_data_assoc_arry[$pID]['comment_date'] = $comments_content_data[$cID]['comment_date'];
				$posts[] = $posts_data_assoc_arry[$pID];		
			}else {
				$posts_data_assoc_arry[$pID]['post_content'] = $posts_content_excerpt[$pID];
                                $posts_data_assoc_arry[$pID]['post_excerpt'] = $posts_content_excerpt[$pID];
				if ( 'page' == $posts_data_assoc_arry[$pID]['post_type']){
					$posts_data_assoc_arry[$pID]['post_title'] = strip_tags($posts_titles_excerpt[$pID]);
					$posts_data_assoc_arry[$pID]['sphinx_post_title'] = $this->config->admin_options['before_page'].$posts_titles_excerpt[$pID];
				}else{
					$posts_data_assoc_arry[$pID]['post_title'] = strip_tags($posts_titles_excerpt[$pID]);
					$posts_data_assoc_arry[$pID]['sphinx_post_title'] = $this->config->admin_options['before_post'].$posts_titles_excerpt[$pID];
				}
				$posts[] = $posts_data_assoc_arry[$pID];			
			}
		}
		
		////////////////////////////
		//Convert posts array to 
		//posts object required by WP
		////////////////////////////
		
		$obj_posts = null;
		foreach($posts as $index => $post){
			foreach($post as $var => $value){
				$obj_posts[$index]->$var = $value;
			}
		}

		return $obj_posts;
	}

	
	/**
	 * Return modified blog title 
	 *
	 * @param string $title
	 * @return string
	 */
	function wp_title($title = '')
	{            
            return urldecode($title);
	}

        /**
	 * Return modified post title
	 *
	 * @param string $title
	 * @return string
	 */
        function the_title($title = '')
	{
            global $post;

            if (!is_search() || !in_the_loop()) return $title;       
            
            return $post->sphinx_post_title;
	}
	
	
	/**
	 * Custom title Tag for post title
	 *
	 * @return unknown
	 */
	function sphinx_the_title()
	{		
            return the_title();
	}
	
	/**
	 * Replace post time to commen time
	 *
	 * @param string $the_time - post time
	 * @param string $d - time format
	 * @return string
	 */
	function the_time($the_time, $d)
	{
		global $post;
		if (!$post->comment_id){	
			return $the_time;
		}	
		if ($d == ''){
			$the_time = date(get_option('time_format'), strtotime($post->comment_date));
		}else {
			$the_time = date($d, strtotime($post->comment_date));
		}
		return $the_time;
	}
	
	/**
	 * Replace post author name to comment author name
	 *
	 * @param string $display_name - post author name
	 * @return string
	 */
	function the_author($display_name)
	{
		global $post;
		if (empty($post->comment_id)){
			return $display_name;
		}
		return $post->comment_author;
	}
	
	/**
	 * Return modified permalink for comments
	 *
	 * @param string $permalink
	 * @return string
	 */
	function the_permalink($permalink = '')
	{
		global $post;
		
		if (!empty($post->comment_id)){
			return $permalink.'#comment-'.$post->comment_id;
		} else {
			return $permalink;
		}
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
		global $post;
		
		if (empty($post->comment_id)){
			return $permalink;
		}
		
		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			'%pagename%'
		);
			
		$permalink = get_option('permalink_structure');
	
		if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending')) ) {
			//Fix comment date to post date
			$unixtime = strtotime($post->post_date_orig);
			
			$category = '';
			if (strpos($permalink, '%category%') !== false) {
				$cats = get_the_category($post->ID);
				if ( $cats ){
					usort($cats, '_usort_terms_by_ID'); // order by ID
				}
				$category = $cats[0]->slug;
				if ( $parent=$cats[0]->parent ){
					$category = get_category_parents($parent, FALSE, '/', TRUE) . $category;
				}
			}
	
			$authordata = get_userdata($post->post_author);
                        $author = '';
                        if (is_object($authordata)){
                            $author = $authordata->user_nicename;
                        }
			$date = explode(" ",date('Y m d H i s', $unixtime));
			$rewritereplace =
			array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post->post_name,
				$post->ID,
				$category,
				$author,
				$post->post_name,
			);
			$permalink = get_option('home') . str_replace($rewritecode, $rewritereplace, $permalink);
			$permalink = user_trailingslashit($permalink, 'single');
			return $permalink;
		} else { // if they're not using the fancy permalink option
			$permalink = get_option('home') . '/?p=' . $post->ID;
			return $permalink;
		}
	}
	
	 /**
	 * Return Sphinx based Excerpts with highlitted words
	 *
	 * @param array $post_content - keys of array is id numbers of search results
	 * can be as _title or empty
	 * @param string $isTitle - it is postfix for array key, can be as 'title' for titles or '' for contents
	 * used to add tags around titles or contents
	 * @return string
	 */
	function get_excerpt($post_content, $isTitle = false)
	{
            $sphinx = $this->config->init_sphinx();
		$is_string = false;
		if(empty($post_content)) return array(); 
			
		if ($isTitle){
			$isTitle = "_title";	
		}

		//strip html tags
		//strip user defined tag
		foreach ($post_content as $post_key => $post_value){
			$post_content[$post_key] = $this->strip_udf_tags($post_value, true);
		}
		
		$opts = array(
					'limit'  => $this->config->admin_options['excerpt_limit'],
					'around' => $this->config->admin_options['excerpt_around'],
					'chunk_separator' => $this->config->admin_options['excerpt_chunk_separator'],
					'after_match' => '{sphinx_after_match}',//$this->config->admin_options['excerpt_after_match'.$isTitle],
					'before_match' => '{sphinx_before_match}' //$this->config->admin_options['excerpt_before_match'.$isTitle]
					);
					
		$excerpts = $sphinx->BuildExcerpts(
                    $post_content,
                    $this->config->admin_options['sphinx_index'].'main',
                    $this->search_string,
                    $opts
		); 
		//to do something usefull with error
		if ( $excerpts === false ){
			$error = $sphinx->getLastError();
			if (preg_match('/connection/', $error) and preg_match('/failed/', $error)) {
				$this->is_searchd_up = false;
			}
			return false;
		}


            $sphinx_after_match = stripslashes($this->config->admin_options['excerpt_after_match'.$isTitle]);
            $sphinx_before_match = stripslashes($this->config->admin_options['excerpt_before_match'.$isTitle]);
            $i = 0;
            foreach ($post_content as $k=>$v){
		$excerpts[$i] = str_replace(
				array('{sphinx_after_match}', '{sphinx_before_match}'),
				array($sphinx_after_match, $sphinx_before_match),
				$excerpts[$i]
		);
                $post_content[$k] = $excerpts[$i];
		$i++;
            }
        
            return $post_content;
	}
	
	/**
	 * Clear content from user defined tags
	 *
	 * @param unknown_type $content
	 * @return unknown
	 */
	function the_content($content = '')
	{
		$content = $this->strip_udf_tags($content, false);
		return $content;
	}
	
	/**
	 * Strip html and user defined tags
	 *
	 * @param string $str
	 * @return string
	 */
	function strip_udf_tags($str, $strip_tags = false)
	{
		if ($strip_tags)
			$str = strip_tags($str, $this->config->admin_options['excerpt_before_match'].
									$this->config->admin_options['excerpt_after_match']);
		if (!empty($this->config->admin_options['strip_tags'])){
			foreach (explode("\n", $this->config->admin_options['strip_tags']) as $tag){
				$tag = trim($tag);
				if (empty($tag)) continue;
				$str = str_replace($tag, '', $str);
			}
		}
		return $str;
	}

        function get_search_string()
        {
            return $this->search_string_original;
        }
	
	/**
	 * Save statistic by about each search query
	 *
	 * @param string $keywords
	 * @return boolean
	 */
	function insert_sphinx_stats($keywords_full)
	{
		global $wpdb, $table_prefix;

		if (is_paged()) return;
		
		$keywords = $this->clear_from_tags($keywords_full);
		$keywords = trim($keywords);
		$keywords_full = trim($keywords_full);

                $sql = "select status from {$table_prefix}sph_stats
                where keywords_full = '".$wpdb->escape($keywords_full)."'
                    limit 1";
                $status = $wpdb->get_var($sql);
                $status = intval($status);

		$sql = "INSERT INTO 
                            {$table_prefix}sph_stats
                            (keywords, keywords_full, date_added, status)
                        VALUES
                            ('".mysql_real_escape_string($keywords)."',
                             '".mysql_real_escape_string($keywords_full)."',
                            NOW(),
                            $status
                        )";

		$wpdb->query($sql);
		return true;
	}
	
	/**
	 * Return TOP-N popual search keywords 
	 *
	 * @param integer $limit
	 * @param integer $width
	 * @param string $break
	 * @return array
	 */
    function sphinx_stats_top_ten($limit = 10, $width = 0, $break = '...')
    {
	$keywords = $this->search_string_original;

        //try to get related results on search page
        if (is_search() && !empty($keywords)){
            $results = $this->sphinx_stats_related($keywords, $limit, $width, $break);
            if (!empty($results)){
                return $results;
            }
        }
        $results = $this->sphinx_stats_top($limit, $width, $break);
		
	return $results;
    }


    function sphinx_stats_top($limit = 10, $width = 0, $break = '...', $approved=false, $period_limit = 30, $start = 0)
    {
        global $wpdb, $table_prefix;

        $sphinx = $this->config->init_sphinx();

        $sphinx->SetLimits($start, $limit + 30, $this->config->admin_options['sphinx_max_matches']);
        if ($approved){
            $status = array(1);
        } else {
            $status = array(1,0);
        }
        $sphinx->SetFilter('status', $status);

        if ($period_limit) {
            $minTime = strtotime("-{$period_limit} days");
            $sphinx->SetFilterRange('date_added', $minTime, time());
        }
        
        $sphinx->SetGroupBy ( "keywords_crc", SPH_GROUPBY_ATTR, "@count desc" );


        $res = $sphinx->Query('',
                $this->config->get_option('sphinx_index').'stats');

        if (empty($res['matches']) || !is_array($res['matches'])){
            return array();
        }        
        $this->_top_ten_total = $res['total'];

        $ids = array_keys($res['matches']);

        $sql = "SELECT
                    distinct keywords_full,
                    keywords,
                    date_added
		FROM
                    {$table_prefix}sph_stats
                WHERE
                    id in (".  implode(",", $ids).")
                ORDER BY FIELD(id, ".  implode(",", $ids).")
		LIMIT
                    ".($limit+30)."" ;
	$results = $wpdb->get_results($sql);

	$results = $this->make_results_clear($results, $limit, $width, $break);

	return $results;
    }

    function get_top_ten_total()
    {
        return $this->_top_ten_total;
    }

    function sphinx_stats_related($keywords, $limit = 10, $width = 0, $break = '...', $approved = false)
    {
        global $wpdb, $table_prefix;

        $sphinx = $this->config->init_sphinx();
        $sphinx->SetMatchMode ( SPH_MATCH_ANY );

        $keywords = $this->clear_keywords($keywords);
        $keywords = $this->unify_keywords($keywords);

        if ($approved){
            $status = array(1);
        } else {
            $status = array(1,0);
        }
        $sphinx->SetFilter('status', $status);

        $sphinx->SetLimits(0, $limit + 30, $this->config->admin_options['sphinx_max_matches']);
        $sphinx->SetGroupBy ( "keywords_crc", SPH_GROUPBY_ATTR, "@weight desc" );

        $res = $sphinx->Query($keywords,
                $this->config->get_option('sphinx_index').'stats');
        if (empty($res['matches']) || !is_array($res['matches'])){
            return false;
        }
        $ids = array_keys($res['matches']);

        $sql = "SELECT
                    keywords,
                    keywords_full
                FROM
                    {$table_prefix}sph_stats
		WHERE
                    id in (".  implode(",", $ids).")   
                    and keywords_full != '".trim($wpdb->escape($keywords))."'
                ORDER BY FIELD(id, ".  implode(",", $ids).")
		LIMIT 
		 ".($limit+30)."" ;

	$results = $wpdb->get_results($sql);

        $results = $this->make_results_clear($results, $limit, $width, $break);

	return $results;
    }

    function sphinx_stats_latest($limit = 10, $width = 0, $break = '...', $approved=false)
    {
        global $wpdb, $table_prefix;
        
        $sphinx = $this->config->init_sphinx();

        $sphinx->SetLimits(0, $limit + 30, $this->config->admin_options['sphinx_max_matches']);
        if ($approved){
            $status = array(1);
        } else {
            $status = array(1,0);
        }
        $sphinx->SetFilter('status', $status);
        $sphinx->SetGroupBy ( "keywords_crc", SPH_GROUPBY_ATTR, "date_added desc" );
        $sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'date_added');


        $res = $sphinx->Query('',
                $this->config->get_option('sphinx_index').'stats');

        if (empty($res['matches']) || !is_array($res['matches'])){
            return array();
        }

        $ids = array_keys($res['matches']);

        $sql = "SELECT
                    distinct keywords_full,
                    keywords,
                    date_added
		FROM
                    {$table_prefix}sph_stats
                WHERE
                    id in (".  implode(",", $ids).")
                ORDER BY FIELD(id, ".  implode(",", $ids).")
		LIMIT
                    ".($limit+30)."" ;
	$results = $wpdb->get_results($sql);

	$results = $this->make_results_clear($results, $limit, $width, $break);

	return $results;
    }
	
    function make_results_clear($results, $limit, $width = 0, $break = '...')
    {
        $counter = 0;
        $clear_results = array();
        
        foreach ($results as $res){
            if ($counter == $limit){
                break;
            }
            $keywords = $this->clear_censor_keywords($res->keywords);
            if ($keywords == strtolower($res->keywords)){
                $counter++;
            } else {
                continue;
            }
            if ($width && mb_strlen($res->keywords) > $width){
                $res->keywords_cut = mb_substr($res->keywords, 0, $width, "UTF-8") . $break;
            } else {
		$res->keywords_cut = $res->keywords;
            }
            $clear_results[] = $res;
        }
	return $clear_results;
    }
	
	/**
	 * Is sphinx top ten is related
	 *
	 * @return boolean
	 */
	function sphinx_stats_top_ten_is_related()
	{
		return $this->top_ten_is_related;
	}
	
	/**
	 * Is sphinx daemon running
	 *
	 * @return boolean
	 */
	function sphinx_is_up()
	{
		return $this->is_searchd_up;
	}

	/**
	 * Remove non-valuable keywords from search string
	 *
	 * @param string $keywords
	 * @return string
	 */
	function clear_keywords($keywords)
	{
		$temp = strtolower(trim($keywords));
		
		$prepositions = array('aboard' , 'about' , 'above' , 'absent' , 'across' , 'after' , 'against' , 'along' , 'alongside' , 
							'amid' , 'amidst' , 'among' , 'amongst' , 'into ' , 'onto' , 'around' , 'as' , 'astride' , 'at' , 'atop' , 
							'before' , 'behind' , 'below' , 'beneath' , 'beside' , 'besides' , 'between' , 'beyond' , 'by' , 'despite' , 
							'down' , 'during' , 'except' , 'following' , 'for' , 'from' , 'in' , 'inside' , 'into' , 'like' , 'mid' ,
							 'minus' , 'near' , 'nearest' , 'notwithstanding' , 'of' , 'off' , 'on' , 'onto' , 'opposite' , 
							 'out' , 'outside' , 'over' , 'past' , 're' , 'round' , 'since' , 'through' , 'throughout' , 
							 'till' , 'to' , 'toward' , 'towards' , 'under' , 'underneath' , 'unlike' , 'until' , 'up' , 
							 'upon' , 'via' , 'with' , 'within' , 'without' , 'anti', 'betwixt' , 'circa' , 'cum' , 'per' , 
							 'qua' , 'sans' , 'unto' , 'versus' , 'vis-a-vis' , 'concerning' , 'considering' , 'regarding');
		$twoWordPrepositions = array('according to' , 'ahead of' , 'as to' , 'aside from' , 'because of' , 'close to' , 
										'due to' , 'far from' , 'in to' , 'inside of' , 'instead of' , 'on to' , 'out of' , 
										'outside of' , 'owing to' , 'near to' , 'next to' , 'prior to' , 'subsequent to');
		$threeWordPrepositions = array('as far as' , 'as well as' , 'by means of' , 'in accordance with' , 'in addition to' , 
											'in front of' , 'in place of' , 'in spite of' , 'on account of' , 'on behalf of' , 
											'on top of' , 'with regard to' , 'in lieu of');
		$coordinatingConjuctions = array('for', 'and', 'nor', 'but', 'or', 'yet', 'so', 'not');
		
		$articles = array('a', 'an', 'the', 'is', 'as');
		
		$stopWords = array_merge($prepositions, $twoWordPrepositions);
		$stopWords = array_merge($stopWords, $threeWordPrepositions);
		$stopWords = array_merge($stopWords, $coordinatingConjuctions);
		$stopWords = array_merge($stopWords, $articles);
		foreach ($stopWords as $k=>$word){
			$stopWords[$k] = '/\b'.preg_quote($word).'\b/';
		}
		
		$temp = preg_replace($stopWords, ' ', $temp);		
		$temp = str_replace('"', ' ', $temp);
		$temp = preg_replace('/\s+/', ' ', $temp);
		$temp = trim($temp);
		//if (empty($temp)) return '';
		
		//$temp = trim(preg_replace('/\s+/', ' ', $temp));
		
		return $temp;
	}
	
	function clear_censor_keywords($keywords)
	{
		$temp = strtolower(trim($keywords));
		
		$censorWords = array(
			"ls magazine","89","www.89.com","El Gordo Y La Flaca Univicion.Com",
			"ls-magazine","big tits","lolita","google","porsche","none","shemale","buy tramadol now","generic cialis",
			"cunt","pussy","c0ck","twat","clit","bitch","fuk",
			'sex','nude','porn','naked','teen','pissing','virgin',
			'fuck','adult','lick','suck','porno','asian','dick','penis','slut','masturb',
			'xxx','lesbian','ass','bitch','anal','gay',
			'incest','masochism','sadism','viagra','sperm','breast',
			'rape','beastality','hardcore','eroti','amateur','vibrator','vagin','clitor',
			'menstruation','anus', 'blow job', 'srxy', 'sexsy', 'sexs', 'girls',
			'blowjob', 'cock', 'cum', 'fetish', 'sexy', 'youporn',
			'4r5e','5h1t','5hit',
			'a55', 'anal', 'ar5e','arrse','arse',
			'ass','ass-fucker','assfucker','assfukka','asshole','asswhole','b00bs','ballbag','balls','ballsack','blowjob',
			'boiolas','boobs','booobs','boooobs','booooobs','booooooobs','buceta',
			'bunny fucker','buttmuch','c0ck','c0cksucker','cawk','chink','cipa','cl1t','clit','clit','clits','cnut',
			'cock','cock-sucker','cockface','cockhead',
			'cockmunch','cockmuncher','cocksucker','cocksuka','cocksukka','cok','cokmuncher','coksucka','cox','cum',
			'cunt','cyalis','dickhead','dildo','dirsa','dlck','dog-fucker','dogging','doosh','duche',
			'f u c k e r','fag','faggitt','faggot','fannyfucker','fanyy','fcuk','fcuker','fcuking','feck','fecker',
			'fook','fooker','fuck','fuck','fucka','fucker','fuckhead','fuckin','fucking','fuckingshitmother\'fucker',
			'fuckwhit','fuckwit','fuk','fuker','fukker','fukkin','fukwhit','fukwit','fux','fux0r','gaylord','heshe',
			'hoare','hoer','hore','jackoff','jism','kawk','knob','knobead','knobed','knobhead','knobjocky',
			'knobjokey','m0f0','m0fo','m45terbate',
			'ma5terb8','ma5terbate','master-bate','masterb8','masterbat*','masterbat3','masterbation','masterbations',
			'masturbate','mo-fo','mof0','mofo','motherfucker','motherfuckka','mutha','muthafecker','muthafuckker',
			'muther','mutherfucker','n1gga','n1gger','nazi','nigg3r','nigg4h',
			'nigga','niggah','niggas','niggaz','nigger','nob','nob jokey','nobhead','nobjocky','nobjokey',
			'numbnuts','nutsack','penis','penisfucker','phuck','pigfucker','pimpis','piss','pissflaps',
			'porn','prick','pron','pusse','pussi',
			'pussy','rimjaw','rimming','schlong','scroat','scrote','scrotum','sh!+','sh!t','sh1t','shag',
			'shagger','shaggin','shagging','shemale','shi+','shit','shit','shitdick','shite','shited','shitey',
			'shitfuck','shithead','shitter','slut','smut','snatch','spac','t1tt1e5','t1tties','teets','teez',
			'testical','testicle','titfuck','tits','titt','tittie5','tittiefucker','titties','tittyfuck',
			'tittywank','titwank','tw4t','twat','twathead','twatty','twunt','twunter','wang','wank',
			'wanker','wanky','whoar','whore','willies','willy');
		
		if (!empty($this->config->admin_options['censor_words'])){
			$censorWordsAdminOptions = explode("\n", $this->config->admin_options['censor_words']);
			foreach($censorWordsAdminOptions as $k => $v){
				$censorWordsAdminOptions[$k] = trim($v);
			}
			$censorWords = array_unique(array_merge($censorWords, $censorWordsAdminOptions)); 
		}		
		foreach ($censorWords as $k=>$word){
			$censorWords[$k] = '/'.preg_quote($word).'/';
		}
		
		$temp = preg_replace($censorWords, ' ', $temp);
		$temp = str_replace('"', ' ', $temp);
		$temp = preg_replace('/\s+/', ' ', $temp);
		$temp = trim($temp);
		
		return $temp;
	}
	
	/**
	 * Remove search tags from search keyword
	 *
	 * @param string $keywords
	 * @return string
	 */
	function clear_from_tags($keywords)
	{
		$stopWords = array('@title', '@body', '@category', '!', '-', '~', '(', ')', '|');
		$keywords = trim(str_replace($stopWords, ' ', $keywords));
			
		if (empty($keywords)) return '';
		
		$keyword = trim(preg_replace('/\s+/', ' ', $keywords));
		
		return $keyword;
	}
	
	function get_type_count($type)
	{
		switch ($type){
			case 'posts':
				return $this->posts_count;
			case 'pages':
				return $this->pages_count;
			case 'comments':
				return $this->comments_count;
			default:
				return 0;
		}
	}

  /**
   * Check whether keywords string has full matches
   * 
   * @param string $keywords
   * @return boolean
   */
  function has_full_matches($keywords)
  {
        $sphinx = $this->config->init_sphinx();
      
        $keywords = $this->unify_keywords($keywords);
        $sphinx->ResetFilters();
        $sphinx->ResetGroupBy();
        $sphinx->SetLimits(0, 1);
        $sphinx->SetMatchMode(SPH_MATCH_ALL);
        $res = $sphinx->Query($keywords, $this->config->admin_options['sphinx_index']);
        return !empty($res['matches']);
  }

  function unify_keywords($keywords)
  {
      //replace key-buffer to key buffer
      //replace key -buffer to key -buffer
      //replace key- buffer to key buffer
      //replace key - buffer to key buffer
      $keywords = preg_replace("#([\w\S])\-([\w\S])#", "\$1 \$2", $keywords);
      $keywords = preg_replace("#([\w\S])\s\-\s([\w\S])#", "\$1 \$2", $keywords);
      $keywords = preg_replace("#([\w\S])-\s([\w\S])#", "\$1 \$2", $keywords);
      return $keywords;
  }

}
