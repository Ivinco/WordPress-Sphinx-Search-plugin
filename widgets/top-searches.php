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
 * TopSearchesWidget Class
 */
class TopSearchesWidget extends WP_Widget
{
    /** constructor */
    function  TopSearchesWidget()
    {
        $widget_ops = array('classname' => 'TopSearchesWidget',
                            'description' => 'Sphinx related/top search terms' );
        $this->WP_Widget('TopSearchesWidget', 'Sphinx Related/Top Searches', $widget_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance)
    {
        extract( $args );
        $limit = !empty($instance['limit']) ? $instance['limit'] : 10;
        $width = !empty($instance['width']) ? $instance['width'] : 0;
        $break = !empty($instance['break']) ? $instance['break'] : '...';
        
        $top_words_html = $this->get_top($limit, $width, $break);
        
        if ( $this->is_related ){
            $title = apply_filters('widget_title', $instance['title_rel']);
        } else {
            $title = apply_filters('widget_title', $instance['title_top']);
        }
        
        echo $before_widget;
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }
        echo $top_words_html;
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title_rel'] = strip_tags($new_instance['title_rel']);
        $instance['title_top'] = strip_tags($new_instance['title_top']);
        $instance['limit'] = strip_tags($new_instance['limit']);
        $instance['width'] = strip_tags($new_instance['width']);
        $instance['break'] = strip_tags($new_instance['break']);
        return $instance;
    }

    /** @see WP_Widget::form */

    function form($instance) {

        $title_rel = !empty($instance['title_rel']) ? esc_attr($instance['title_rel']) : 'Related Searches';
        $title_top = !empty($instance['title_top']) ? esc_attr($instance['title_top']) : 'Top Searches';
        $limit = !empty($instance['limit']) ? esc_attr($instance['limit']) : 10;
        $width = !empty($instance['width']) ? esc_attr($instance['width']) : 0;
        $break = !empty($instance['break']) ? esc_attr($instance['break']) : '...';
        ?>
            <p><label for="<?php echo $this->get_field_id('title_top'); ?>">
            <?php _e('Title Top:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title_top'); ?>"
                   name="<?php echo $this->get_field_name('title_top'); ?>"
                   type="text" value="<?php echo $title_top; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('title_rel'); ?>">
            <?php _e('Title Related:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title_rel'); ?>"
                   name="<?php echo $this->get_field_name('title_rel'); ?>"
                   type="text" value="<?php echo $title_rel; ?>" />
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

    function get_top($limit = 10, $width = 0, $break = '...')
    {
        global $defaultObjectSphinxSearch;

	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_top_ten($limit, $width, $break);
        $this->is_related = $defaultObjectSphinxSearch->frontend->sphinx_stats_top_ten_is_related();
        $html = '';
	$html .= "<ul>";
            foreach ($result as $res)
            {
                $html .= "<li><a href='". get_bloginfo('url') ."/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
            }
	$html .= "</ul>";
        return $html;
    }
}


