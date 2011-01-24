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

        $title_rel = apply_filters('widget_title', $instance['title_rel']);
        $title_top = apply_filters('widget_title', $instance['title_top']);
        $width = !empty($instance['width']) ? $instance['width'] : 0;
        $break = !empty($instance['break']) ? $instance['break'] : '...';
        $custom_terms_top = !empty($instance['custom_terms_top']) ? $instance['custom_terms_top'] : '';
        $front_show = !empty($instance['front_show']) ? $instance['front_show'] : 'show';
        $posts_show = !empty($instance['front_show']) ? $instance['posts_show'] : 'show_related';
        $search_show = !empty($instance['front_show']) ? $instance['search_show'] : 'show_related';

        $show_widget = false;
        //if it is post
        if (is_search() && $search_show != 'hide'){
            $limit = !empty($instance['limit']) ? $instance['search_limit'] : 10;
            if ( $search_show == 'show_related' ){
                $title = $title_rel;
                $words_html = $this->get_related($_GET['s'], $limit, $width, $break);
            }
            if (empty($words_html) || $search_show == 'show_top') {
                $title = $title_top;
                $words_html = $this->get_top($limit, $width, $break, $custom_terms_top);
            }
            $show_widget = true;
        } else if ( is_single() && $posts_show != 'hide'){
            $limit = !empty($instance['limit']) ? $instance['posts_limit'] : 10;
            if ( $posts_show == 'show_related' ){
                $title = $title_rel;
                $keywords = single_post_title( '', false );
                $words_html = $this->get_related($keywords, $limit, $width, $break);
            }
            if (empty($words_html) || $posts_show == 'show_top') {
                $title = $title_top;
                $words_html = $this->get_top($limit, $width, $break, $custom_terms_top);
            }
            $show_widget = true;
        } else if ($front_show != 'hide'){
            $title = $title_top;
            $limit = !empty($instance['limit']) ? $instance['front_limit'] : 10;
            $words_html = $this->get_top($limit, $width, $break, $custom_terms_top);
            $show_widget = true;
        }
        
        if ($show_widget){
            echo $before_widget;
            if ( $title ) {
                echo $before_title . $title . $after_title;
            }
            echo $words_html;
            echo $after_widget;
        }
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	$instance = $old_instance;
	$instance['title_rel'] = strip_tags($new_instance['title_rel']);
        $instance['title_top'] = strip_tags($new_instance['title_top']);
        $instance['front_limit'] = strip_tags($new_instance['front_limit']);
        $instance['front_show'] = strip_tags($new_instance['front_show']);
        $instance['posts_limit'] = strip_tags($new_instance['posts_limit']);
        $instance['posts_show'] = strip_tags($new_instance['posts_show']);
        $instance['search_limit'] = strip_tags($new_instance['search_limit']);
        $instance['search_show'] = strip_tags($new_instance['search_show']);
        $instance['custom_terms_top'] = strip_tags($new_instance['custom_terms_top']);
        $instance['width'] = strip_tags($new_instance['width']);
        $instance['break'] = strip_tags($new_instance['break']);
        return $instance;
    }

    /** @see WP_Widget::form */

    function form($instance) {

        $title_rel = !empty($instance['title_rel']) ? esc_attr($instance['title_rel']) : 'Related Searches';
        $title_top = !empty($instance['title_top']) ? esc_attr($instance['title_top']) : 'Top Searches';
        $front_show = !empty($instance['front_show']) ? esc_attr($instance['front_show']) : 'show';
        $front_limit = !empty($instance['front_limit']) ? esc_attr($instance['front_limit']) : 10;
        $posts_show = !empty($instance['posts_show']) ? esc_attr($instance['posts_show']) : 'show_related';
        $posts_limit = !empty($instance['posts_limit']) ? esc_attr($instance['posts_limit']) : 10;
        $search_show = !empty($instance['search_show']) ? esc_attr($instance['search_show']) : 'show_related';
        $search_limit = !empty($instance['search_limit']) ? esc_attr($instance['search_limit']) : 10;
        $custom_terms_top = !empty($instance['custom_terms_top']) ? esc_attr($instance['custom_terms_top']) : '';
        $width = !empty($instance['width']) ? esc_attr($instance['width']) : 0;
        $break = !empty($instance['break']) ? esc_attr($instance['break']) : '...';
        ?>
            <p><label for="<?php echo $this->get_field_id('title_top'); ?>">
            <?php _e('Title top:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title_top'); ?>"
                   name="<?php echo $this->get_field_name('title_top'); ?>"
                   type="text" value="<?php echo $title_top; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('title_rel'); ?>">
            <?php _e('Title related:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title_rel'); ?>"
                   name="<?php echo $this->get_field_name('title_rel'); ?>"
                   type="text" value="<?php echo $title_rel; ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('front_show'); ?>">
                <?php _e('Show on front page:'); ?>
                <select class="widefat" id="<?php echo $this->get_field_id('front_show'); ?>"
                        name="<?php echo $this->get_field_name('front_show'); ?>">>
                    <option value="show" 
                        <?php echo ($front_show =='show')?'  selected="selected"':''?>
                    >Show top searches</option>
                    <option value="hide"
                        <?php echo ($front_show =='hide')?'  selected="selected"':''?>
                     >Do not show</option>
                </select>
             </label></p>
            <p><label for="<?php echo $this->get_field_id('front_limit'); ?>">
            <?php _e('Number of results:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('front_limit'); ?>"
                   name="<?php echo $this->get_field_name('front_limit'); ?>"
                   type="text" value="<?php echo $front_limit; ?>" />
            </label></p>

            <p><label for="<?php echo $this->get_field_id('posts_show'); ?>">
                <?php _e('Show on post pages:'); ?>
                <select class="widefat" id="<?php echo $this->get_field_id('posts_show'); ?>"
                        name="<?php echo $this->get_field_name('posts_show'); ?>">
                    <option value="show_related"
                        <?php echo ($posts_show =='show_related')?'  selected="selected"':''?>
                    >Show related searches</option>
                    <option value="show_top"
                        <?php echo ($posts_show =='show_top')?'  selected="selected"':''?>
                    >Show top searches</option>
                    <option value="hide"
                        <?php echo ($posts_show =='hide')?'  selected="selected"':''?>
                     >Do not show</option>
                </select>
             </label></p>
            <p><label for="<?php echo $this->get_field_id('posts_limit'); ?>">
            <?php _e('Number of results:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('posts_limit'); ?>"
                   name="<?php echo $this->get_field_name('posts_limit'); ?>"
                   type="text" value="<?php echo $posts_limit; ?>" />
            </label></p>

            <p><label for="<?php echo $this->get_field_id('search_show'); ?>">
                <?php _e('Show on search pages:'); ?>
                <select class="widefat" id="<?php echo $this->get_field_id('search_show'); ?>"
                        name="<?php echo $this->get_field_name('search_show'); ?>">
                    <option value="show_related"
                        <?php echo ($search_show =='show_related')?'  selected="selected"':''?>
                    >Show related searches</option>
                    <option value="show_top"
                        <?php echo ($search_show =='show_top')?'  selected="selected"':''?>
                    >Show top searches</option>
                    <option value="hide"
                        <?php echo ($search_show =='hide')?'  selected="selected"':''?>
                     >Do not show</option>
                </select>
             </label></p>
            <p><label for="<?php echo $this->get_field_id('search_limit'); ?>">
            <?php _e('Number of results:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('search_limit'); ?>"
                   name="<?php echo $this->get_field_name('search_limit'); ?>"
                   type="text" value="<?php echo $search_limit; ?>" />
            </label></p>

            <p><label for="<?php echo $this->get_field_id('custom_terms_top'); ?>">
            <?php _e('Custom search terms on top of "Top" searches:'); ?>
            <textarea class="widefat" cols="20" rows="5"
                   id="<?php echo $this->get_field_id('custom_terms_top'); ?>"
                   name="<?php echo $this->get_field_name('custom_terms_top'); ?>"
                   ><?php echo $custom_terms_top; ?></textarea>
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

    function get_top($limit = 10, $width = 0, $break = '...', $custom_top='')
    {
        global $defaultObjectSphinxSearch;

        $custom_top = trim($custom_top);
        $custom_top_arry = explode("\n", $custom_top);        

	$html = "<ul>";
        if (!empty($custom_top_arry)){
            foreach($custom_top_arry as $ind => $term){
                if ($limit <= 0){
                    break;
                }
                $term = trim($term);
                if (empty($term)){
                    unset($custom_top_arry[$ind]);
                    continue;
                }
                $limit--;
                $html .= "<li><a href='". get_bloginfo('url') ."/?s=".urlencode(stripslashes($term))."' title='".htmlspecialchars(stripslashes($term), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($term), ENT_QUOTES)."</a></li>";
            }
        }

        if ($limit <= 0){
            $html .= "</ul>";
            return $html;
        }

	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_top($limit, $width, $break);
        if (empty($result)){
            return false;
        }
        
        foreach ($result as $res){
            $html .= "<li><a href='". get_bloginfo('url') ."/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
        }
        
	$html .= "</ul>";
        return $html;
    }

    function get_related($keywords, $limit = 10, $width = 0, $break = '...')
    {
        global $defaultObjectSphinxSearch;

	$result = $defaultObjectSphinxSearch->frontend->sphinx_stats_related($keywords, $limit, $width, $break);
        if (empty($result)){
            return false;
        }
        $html = '';
	$html .= "<ul>";
        foreach ($result as $res){
            $html .= "<li><a href='". get_bloginfo('url') ."/?s=".urlencode(stripslashes($res->keywords_full))."' title='".htmlspecialchars(stripslashes($res->keywords), ENT_QUOTES)."'>".htmlspecialchars(stripslashes($res->keywords_cut), ENT_QUOTES)."</a></li>";
        }
	$html .= "</ul>";
        return $html;
    }
}


