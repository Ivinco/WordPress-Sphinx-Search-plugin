<?php
/*
    Copyright 2008  &copy; Ivinco LTD  (email : opensource@ivinco.com)

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
function ss_isComment() {
    global $post;
    return $post->comment_ID;
}
		
function ss_search_bar($is_sidebar = false)
{
    global $defaultObjectSphinxSearch;
	
    if ('true' == $defaultObjectSphinxSearch->frontend->params['search_posts'])
        $search_posts = "checked='checked'";
    else $search_posts = '';
		
    if ('true' == $defaultObjectSphinxSearch->frontend->params['search_pages'])
        $search_pages = "checked='checked'";
    else $search_pages = '';
		
    if ('true' == $defaultObjectSphinxSearch->frontend->params['search_comments'])
        $search_comments = "checked='checked'";
    else $search_comments = '';
		
    $search_sortby_relevance = $search_sortby_date = '';
    if (!empty($defaultObjectSphinxSearch->frontend->params['search_sortby']) &&
        $defaultObjectSphinxSearch->frontend->params['search_sortby'] == 'date'
			) 
	$search_sortby_date = 'checked="true"';
    else
        $search_sortby_relevance = 'checked="false"';;
		
    if ($is_sidebar)
        require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_search_bar.htm');
    else
        require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_search_panel.htm');
}
	
function ss_top_searches($limit = 10, $width = 0, $break = '...')
{
    global $defaultObjectSphinxSearch;
		
    $result = $defaultObjectSphinxSearch->frontend->sphinx_stats_top_ten($limit, $width, $break);
    echo "<ul>";
    foreach ($result as $res){
        echo "<li><a href='/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
    }
    echo "</ul>";
}
	
function ss_latest_searches($limit = 10, $width = 0, $break = '...')
{
    global $defaultObjectSphinxSearch;
    $result = $defaultObjectSphinxSearch->frontend->sphinx_stats_latest($limit, $width, $break);
    echo "<ul>";
    foreach ($result as $res){
	echo "<li><a href='/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
    }
    echo "</ul>";
}
	
function ss_isSphinxUp() {
    global $defaultObjectSphinxSearch;
    return $defaultObjectSphinxSearch->frontend->sphinx_is_up();
}
	
function ss_top_ten_is_related() {
    global $defaultObjectSphinxSearch;
    return $defaultObjectSphinxSearch->frontend->sphinx_stats_top_ten_is_related();
}
	
function sphinx_the_title() {
    global $defaultObjectSphinxSearch;
echo $defaultObjectSphinxSearch->frontend->sphinx_the_title();
}
	
function sphinx_get_type_count($type){
    global $defaultObjectSphinxSearch;
    echo $defaultObjectSphinxSearch->frontend->get_type_count($type);
}
	
