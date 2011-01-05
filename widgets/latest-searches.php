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
/**
 * LatestSearchesWidget Class
 */
class LatestSearchesWidget extends WP_Widget
{
    /** constructor */
    function  LatestSearchesWidget()
    {
        $widget_ops = array('classname' => 'SphinxLatestSearchesWidget',
                            'description' => 'Sphinx last search terms' );
        $this->WP_Widget('SphinxLatestSearchesWidget', 'Sphinx Last Searches', $widget_ops);

        //parent::WP_Widget(false, $name = 'SphinxLatestSearchesWidget');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance)
    {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $limit = !empty($instance['limit']) ? $instance['limit'] : 10;
        $width = !empty($instance['width']) ? $instance['width'] : 0;
        $break = !empty($instance['break']) ? $instance['break'] : '...';
        echo $before_widget; 
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }
        $this->get_latest($limit, $width, $break);
        echo $after_widget; 
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        $instance['limit'] = strip_tags($new_instance['limit']);
        $instance['width'] = strip_tags($new_instance['width']);
        $instance['break'] = strip_tags($new_instance['break']);
        return $instance;
    }

    /** @see WP_Widget::form */
    
    function form($instance) {

        $title = !empty($instance['title']) ? esc_attr($instance['title']) : 'Last Searches';
        $limit = !empty($instance['limit']) ? esc_attr($instance['limit']) : 10;
        $width = !empty($instance['width']) ? esc_attr($instance['width']) : 0;
        $break = !empty($instance['break']) ? esc_attr($instance['break']) : '...';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Title:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo $title; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('limit'); ?>">
            <?php _e('Number of results:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>"
                   name="<?php echo $this->get_field_name('limit'); ?>"
                   type="text" value="<?php echo $limit; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Maximum length of search term:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('width'); ?>"
                   name="<?php echo $this->get_field_name('width'); ?>"
                   type="text" value="<?php echo $width; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Break long search term by:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('break'); ?>"
                   name="<?php echo $this->get_field_name('break'); ?>"
                   type="text" value="<?php echo $break; ?>" />
            </label></p>
        <?php

    }

    function get_latest($limit = 10, $width = 0, $break = '...')
    {
        global $defaultObjectSphinxSearch;
        
	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_latest($limit, $width, $break);
	echo "<ul>";
            foreach ($result as $res)
            {
                echo "<li><a href='/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
            }
	echo "</ul>";
    }
}


