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
 * SearcheSidebarWidget Class
 */
class SearchSidebarWidget extends WP_Widget
{
    /** constructor */
    function  SearchSidebarWidget()
    {
        $widget_ops = array('classname' => 'SearchSidebarWidget',
                            'description' => 'Sphinx search sidebar' );
        $this->WP_Widget('SearchSidebarWidget', 'Sphinx Search sidebar',
                $widget_ops);

        //parent::WP_Widget(false, $name = 'SphinxLatestSearchesWidget');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance)
    {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }
        $this->get_sidebar();
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */

    function form($instance) {

        $title = !empty($instance['title']) ? esc_attr($instance['title']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Title:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo $title; ?>" />
            </label></p>
        <?php

    }

    function get_sidebar()
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

	$search_sortby_date_relevance = $search_sortby_relevance = $search_sortby_date = '';
        if (!empty($defaultObjectSphinxSearch->frontend->params['search_sortby'])){
            $ss_sort_by = $defaultObjectSphinxSearch->frontend->params['search_sortby'];
        }
        if ($ss_sort_by == 'date'){
            $search_sortby_date = 'checked="true"';
        } else if ($ss_sort_by == 'date_relevance' ){
            $search_sortby_date_relevance = 'checked="true"';
        } else {
            $search_sortby_relevance = 'checked="true"';
        }

         require_once(SPHINXSEARCH_PLUGIN_DIR.'/templates/sphinx_search_bar.htm');
    }
}


