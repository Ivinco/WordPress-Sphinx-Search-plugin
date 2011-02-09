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
        $show_approved = !empty($instance['show_approved']) ? $instance['show_approved'] : false;
        echo $before_widget; 
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }
        $this->get_latest($limit, $width, $break, $show_approved);
        echo $after_widget; 
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        $instance['limit'] = strip_tags($new_instance['limit']);
        $instance['width'] = strip_tags($new_instance['width']);
        $instance['break'] = strip_tags($new_instance['break']);
        $instance['show_approved'] = strip_tags($new_instance['show_approved']);
        return $instance;
    }

    /** @see WP_Widget::form */
    
    function form($instance) {

        $title = !empty($instance['title']) ? esc_attr($instance['title']) : 'Last Searches';
        $limit = !empty($instance['limit']) ? esc_attr($instance['limit']) : 10;
        $width = !empty($instance['width']) ? esc_attr($instance['width']) : 0;
        $break = !empty($instance['break']) ? esc_attr($instance['break']) : '...';
        $show_approved = !empty($instance['show_approved']) ? esc_attr($instance['show_approved']) : false;
        ?>
            <p>
                <input class="checkbox" id="<?php echo $this->get_field_id('show_approved'); ?>"
                   name="<?php echo $this->get_field_name('show_approved'); ?>"
                   type="checkbox" value="true" <?php echo $show_approved == 'true' ? 'checked="checked"': ''; ?> />
                <label for="<?php echo $this->get_field_id('show_approved'); ?>">
                    <?php _e('Show only approved keywords:'); ?>
                </label>
            </p>
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

    function get_latest($limit = 10, $width = 0, $break = '...', $show_approved=false)
    {
        global $defaultObjectSphinxSearch;
        
	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_latest($limit, $width, $break, $show_approved);
	echo "<ul>";
            foreach ($result as $res)
            {
                echo "<li><a href='". get_bloginfo('url') ."/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
            }
	echo "</ul>";
    }
}


