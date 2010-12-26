<?php
/**
 * LatestSearchesWidget Class
 */
class TopSearchesWidget extends WP_Widget
{
    /** constructor */
    function  TopSearchesWidget()
    {
        $widget_ops = array('classname' => 'TopSearchesWidget',
                            'description' => 'Sphinx top search terms' );
        $this->WP_Widget('TopSearchesWidget', 'Sphinx Top Searches', $widget_ops);
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
        $this->getTop($limit, $width, $break);
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

        $title = !empty($instance['title']) ? esc_attr($instance['title']) : '';
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
            <?php _e('Breake long search term by:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('break'); ?>"
                   name="<?php echo $this->get_field_name('break'); ?>"
                   type="text" value="<?php echo $break; ?>" />
            </label></p>
        <?php

    }

    function getTop($limit = 10, $width = 0, $break = '...')
    {
        global $defaultObjectSphinxSearch;

	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_top_ten($limit, $width, $break);
	echo "<ul>";
            foreach ($result as $res)
            {
                echo "<li><a href='/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
            }
	echo "</ul>";
    }
}


