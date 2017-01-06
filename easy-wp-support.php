<?php
/*
Plugin Name: Easy WP Tutorial
Plugin URI: https://www.motivar.io
Description: Give your clients fast and easy support
Version: 0.5.5
Author: Anastasiou K., Giannopoulos N.
Author URI: https://motivar.io
Text Domain:       github-updater
GitHub Plugin URI: https://github.com/Motivar/easy_wp_support
GitHub Branch:     master
*/

if (!defined('WPINC')) {
    die;
}

add_filter('acf/load_field/name=select_dedicated_page', 'acf_load_tutorial_page_choices');

function acf_load_tutorial_page_choices($field)
{
    $args  = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => '-1'
    );
    $field['choices']               = array();
    $field['choices']['']           = 'Select Page';
    $pages                   = query_posts($args);
    foreach ($pages as $p) {
        $field['choices'][$p->ID] = $p->post_title;
    }
    // return the field
    return $field;

}


add_action('admin_menu', 'settings_options');

function settings_options()
{
    add_options_page('Tutorials Users', 'Tutorials Users', 'manage_options', 'tutorials_users', 'tutorials_users_func');
}

function tutorials_users_func($flag)
{
    global $wp_roles;
    $roles = $wp_roles->get_names();
    $msg   = '';
    
    switch ($flag) {
        case 'check_cur_role':
            $current_user        = wp_get_current_user();
            $user_roles          = $current_user->roles;
            $selected_user_roles = get_option('easy_wp_users');
            if (empty($selected_user_roles)) {
                $selected_user_roles = array();
            }
            
            if (in_array($user_roles[0], $selected_user_roles)) {
                return 1;
            } else {
                return 0;
            }
            
            break;
        default:
            wp_enqueue_style('easy-wp-support-style_css', plugin_dir_url(__FILE__) . 'scripts/easy-wp-support-style.css', array(), '', 'all');
            if (isset($_POST['user_roles']) && !empty($_POST['user_roles'])) {
                update_option('easy_wp_users', $_POST['user_roles']);
            }
            $selected_user_roles = get_option('easy_wp_users');
            if (empty($selected_user_roles)) {
                $selected_user_roles = array();
            }
            
            $msg = '<form action="" method="post"><div class="user_roles_list"><h4>' . __("Choose users", "easy_wp_user_roles_title") . '</h4>';
            
            foreach ($roles as $k => $v) {
                $chk = '';
                if (in_array($k, $selected_user_roles)) {
                    $chk .= ' checked';
                }
                $msg .= '<label for="' . $k . '"><input type="checkbox" name="user_roles[]" value="' . $k . '" id="' . $k . '" ' . $chk . '/> ' . $v . '</label>';
            }
            $msg .= '<input type="submit" value="' . __("Save", "easy_wp_user_roles_save") . '"/></div></form>';
            
            echo $msg;
            break;
    }
    
}

function easy_wp_support_yoast_exlude_prep($response, $attachment, $meta)
{
    
    $check = get_post_meta($response['id'], 'easy_wp_support_yoast_exlude', true) ?: 0;
    if ($check == 1) {
        $response['customClass'] = "easy_wp_support_yoast_exlude";
    } else {
        $response['customClass'] = "";
    }
    return $response;
    
}

add_filter('wp_prepare_attachment_for_js', 'easy_wp_support_yoast_exlude_prep', 10, 3);



/*media meta for Yoast seo*/
function easy_wp_support_img_exclude($form_fields, $post)
{
    $user_flag = tutorials_users_func('check_cur_role');
    if ($user_flag === 1) {
        $yoast = get_option('wpseo_xml') ?: array();
        if (!empty($yoast) && $yoast['enablexmlsitemap'] == 1) {
            $check                                       = get_post_meta($post->ID, 'easy_wp_support_yoast_exlude', true) ?: 0;
            $active                                      = $check == 1 ? 'checked' : '';
            $form_fields['easy_wp_support_yoast_exlude'] = array(
                'label' => 'Remove this media from Yoast sitemap',
                'input' => 'html',
                'html' => '<input type="checkbox" id="easy_wp_support_yoast_exlude" name="easy_wp_support_yoast_exlude" ' . $active . ' value="1"/>'
            );
            return $form_fields;
        }
    }
}
add_filter('attachment_fields_to_edit', 'easy_wp_support_img_exclude', 10, 2);


function easy_wp_support_yoast_exlude_save($post, $attachment)
{
    $user_flag = tutorials_users_func('check_cur_role');
    if ($user_flag === 1) {
        $val       = isset($_POST['easy_wp_support_yoast_exlude']) ? (int) $_POST['easy_wp_support_yoast_exlude'] : 0;
        $yoast     = get_option('wpseo_xml');
        $exl_posts = explode(',', $yoast['excluded-posts']);
        switch ($val) {
            case 1:
                update_post_meta($post['ID'], 'easy_wp_support_yoast_exlude', 1);
                if (!in_array($post['ID'], $exl_posts)) {
                    $exl_posts[] = $post['ID'];
                }
                break;
            default:
                delete_post_meta($post['ID'], 'easy_wp_support_yoast_exlude');
                if (($key = array_search($post['ID'], $exl_posts)) !== false) {
                    unset($exl_posts[$key]);
                }
                break;
        }
        $yoast['excluded-posts'] = implode(',', $exl_posts);
        update_option('wpseo_xml', $yoast);
        return $post;
    }
}
add_filter('attachment_fields_to_save', 'easy_wp_support_yoast_exlude_save', 10, 2);



function easy_wp_support_help()
{
    $user_flag = tutorials_users_func('check_cur_role');
    if ($user_flag === 1) {
        /*$url = site_url();
        print_r($url);*/
        $screen        = get_current_screen();
        /*print_r($screen); */
        $view_page     = $screen->base;
        $taxonomy_name = $screen->taxonomy;
        if ($view_page == 'upload') {
            $yoast = get_option('wpseo_xml') ?: array();
            if (!empty($yoast) && $yoast['enablexmlsitemap'] == 1) {
                echo '<input type="hidden" id="easy_wp_support_exclude_images" value="' . $yoast['excluded-posts'] . '">';
            }
        }
        /*print_r($screen) ;*/
        $post_typee = $screen->post_type;
        if (!empty($post_typee)) {
            $post_typee_array = array(
                'key' => 'easy_wp_support_help_posttypes',
                'value' => serialize(strval($post_typee)),
                'compare' => 'LIKE'
            );
        } else {
            $post_typee_array = array();
        }
        
        if (($view_page == 'edit-tags') || ($view_page == 'term')) {
            $taxonomy_name_array = array(
                'key' => 'easy_wp_tutorials_insert_taxonomy_name',
                'value' => $taxonomy_name,
                'compare' => '='
            );
        } else {
            $taxonomy_name_array = array();
        }
        $args       = array(
            'post_type' => 'easy_wp_support_post',
            'post_status' => 'publish',
            'meta_query' => array(
                $post_typee_array,
                array(
                    'key' => 'easy_wp_support_help_view_page',
                    'value' => $view_page,
                    'compare' => '='
                ),
                $taxonomy_name_array
            )
        );
        $help_posts = get_posts($args);

        /*Search if there are dedicated tutorials for the current post*/
        if ($view_page == 'post'){
            $dedicated_args = array(
                'post_type' => 'easy_wp_support_post',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                    'key' => 'select_dedicated_page',
                    'value' => $current_id,
                    'compare' => '='
                    )
                )
            );
            $dedicated_tutorial = get_posts($dedicated_args);
        }

        $current_id = get_the_ID();

        if ( function_exists('icl_object_id') ) {
            $lan = ICL_LANGUAGE_CODE ;
            if ($lan == 'en'){
                $trans_lan = 'el';
            }
            else{
                $trans_lan = 'en';
            }
            $trans_id = icl_object_id($current_id, 'page', false, $trans_lan);
            $trans_args = array(
            'post_type' => 'easy_wp_support_post',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'select_dedicated_page',
                    'value' => $trans_id,
                    'compare' => '='
                )
            )
        );
        $trans_tutorial = get_posts($trans_args);
        }

        if (!empty($help_posts) || (isset($dedicated_tutorial) && !empty($dedicated_tutorial)) || (isset($trans_tutorial) && !empty($trans_tutorial))) {
            echo '
        <div id="pop_up_button">
        <button class="easy_wp_support_help-button"><a href="#openModal">Help?</a></button>
        <div id="openModal" class="easy_wp_support_modalDialog">
        <div><a href="#easy_wp_support_close" title="Close" class="easy_wp_support_close">X</a>
        <div class="easy_wp_support_pop_up">';
            foreach ($help_posts as $tutorial) {
                $tut_id = $tutorial->ID;
                echo stripslashes($tutorial->post_content);
            }
            if (isset($dedicated_tutorial) && !empty($dedicated_tutorial)){
                foreach ($dedicated_tutorial as $d_tutorial) {
                    $d_tut_id = $d_tutorial->ID;
                    echo stripslashes($d_tutorial->post_content);
                }
            }
            
            if (isset($trans_tutorial) && !empty($trans_tutorial)){
                foreach ($trans_tutorial as $tr_tutorial) {
                    $tr_tut_id = $tr_tutorial->ID;
                    echo stripslashes($tr_tutorial->post_content);
                }
            }
            echo '</div></div></div></div>';
        }
    }
}


/* on save make the right movements*/
add_action('acf/save_post', 'easy_wp_support_save_acf', 20);
function easy_wp_support_save_acf($post_id)
{
    $user_flag = tutorials_users_func('check_cur_role');
    if ($user_flag === 1) {
        if ((!wp_is_post_revision($post_id) && 'auto-draft' != get_post_status($post_id) && 'trash' != get_post_status($post_id))) {
            $tt      = get_post_type($post_id);
            $tttile  = isset($_POST['post_title']) ? $_POST['post_title'] : '';
            $changes = $types = array();
            switch ($tt) {
                case 'easy_wp_support_post':
                    // $repeater = $_POST('ctm_help_step');
                    $steps_arrray     = array_values($_POST['acf']);
                    $post_types_array = $steps_arrray[1];
                    $view_page        = $steps_arrray[2];
                    $posttypes        = array();
                    foreach ($post_types_array as $parray) {
                        $parray      = array_values($parray);
                        $posttypes[] = $parray[0];
                    }
                    update_post_meta($post_id, 'easy_wp_support_help_posttypes', $posttypes);
                    update_post_meta($post_id, 'easy_wp_support_help_view_page', $view_page);
                    $steps_array = $steps_arrray[0];
                    $count       = count($steps_array);
                    $msg         = '<h1>' . $ptitle . '</h1>';
                    foreach ($steps_array as $array) {
                        $array      = array_values($array);
                        $step_title = $array[0];
                        $step_img   = $array[1];
                        $img        = get_the_guid($step_img);
                        $step_desc  = $array[2];
                        
                        $msg .= '<h2>' . $step_title . '</h2>';
                        $msg .= '<div class="tutorial_text">' . wpautop($step_desc) . '</div><br />';
                        if (!empty($step_img)) {
                            $msg .= '<p><img src=' . $img . '></img></p>';
                        }
                    }
                    $changes = array(
                        'post_content' => $msg,
                        'post_title' => ucfirst(strtolower($tttile))
                    );
                    $types   = array(
                        '%s',
                        '%s'
                    );
                    break;
                default:
                    $changes['post_name']  = sanitize_title(easy_wp_support_functions_slugify(easy_wp_support_functions_greeklish($tttile)));
                    $changes['post_title'] = ucfirst($tttile);
                    $types                 = array(
                        '%s',
                        '%s'
                    );
                    break;
                    
            }
            /*update post only if the following exist*/
            if ($tt !== 'page') {
                if (!empty($changes) && !empty($types) && count($changes) == count($types)) {
                    easy_wp_support_functions_update_post($post_id, $changes, $types);
                }
                
            }
        }
    }
}



/* change slug*/
function easy_wp_support_functions_update_post($id, $changes, $types)
{
    /*id, array('post_title'=>$title) */
    global $wpdb;
    $wpdb->update($wpdb->posts, $changes, array(
        'ID' => $id
    ), $types, array(
        '%d'
    ));
}

/*register post type*/
function easy_wp_support_my_custom_posts($post_type)
{
    $all = array(
        array(
            'post' => 'easy_wp_support_post',
            'sn' => 'Tutorial',
            'pl' => 'Tutorials',
            'args' => array(
                'title',
                'editor'
            ),
            'chk' => true,
            'mnp' => 3,
            'icn' => '',
            'slug' => get_option('easy_wp_support_post_slug') ?: 'easy-wp-tutorials',
            'en_slg' => 1
        )
    );
    if ($post_type == 'all') {
        $msg = $all;
    } else {
        foreach ($all as $k) {
            $posttype = $k['post'];
            if ($posttype == $post_type) {
                $msg = $k;
            }
        }
    }
    return $msg;
}

add_action('init', 'easy_wp_support_register_my_cpts');

function easy_wp_support_register_my_cpts()
{
    $current_user = wp_get_current_user();
    $user_roles   = $current_user->roles;
    if (in_array('administrator', $user_roles)) {
        
        $names = easy_wp_support_my_custom_posts('all');
        foreach ($names as $n) {
            $chk          = $n['chk'];
            $hierarchical = '';
            if ($chk == 'true') {
                $hierarchical == 'false';
            } else {
                $hierarchical == 'true';
            }
            $labels = $args = array();
            $labels = array(
                'name' => $n['pl'],
                'singular_name' => $n['sn'],
                'menu_name' => '' . $n['pl'],
                'add_new' => 'New ' . $n['sn'],
                'add_new_item' => 'New ' . $n['sn'],
                'edit' => 'Edit',
                'edit_item' => 'Edit ' . $n['sn'],
                'new_item' => 'New ' . $n['sn'],
                'view' => 'View ' . $n['sn'],
                'view_item' => 'View ' . $n['sn'],
                'search_items' => 'Search ' . $n['sn'],
                'not_found' => 'No ' . $n['pl'],
                'not_found_in_trash' => 'No trushed ' . $n['pl'],
                'parent' => 'Parent ' . $n['sn']
            );
            $args   = array(
                'labels' => $labels,
                'description' => 'My Simple Bookings post type for ' . $n['pl'],
                'public' => $n['chk'],
                'show_ui' => true,
                'has_archive' => $n['chk'],
                'show_in_menu' => true,
                'exclude_from_search' => $n['chk'],
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'hierarchical' => $hierarchical,
                'rewrite' => array(
                    'slug' => $n['post'],
                    'with_front' => true
                ),
                'query_var' => true,
                'supports' => $n['args']
            );
            
            if (!empty($n['slug'])) {
                $args['rewrite']['slug'] = $n['slug'];
            }
            
            if (!empty($n['mnp'])) {
                $args['menu_position'] = $n['mnp'];
            }
            
            if (!empty($n['icn'])) {
                $args['menu_icon'] = $n['icn'];
            }
            register_post_type($n['post'], $args);
            
            if (isset($n['en_slg']) && $n['en_slg'] == 1) {
                add_action('load-options-permalink.php', function($views) use ($n)
                {
                    if (isset($_POST[$n['post'] . '_slug'])) {
                        update_option($n['post'] . '_slug', sanitize_title_with_dashes($_POST[$n['post'] . '_slug']));
                    }
                    
                    add_settings_field($n['post'] . '_slug', __($n['pl'] . ' Slug'), function($views) use ($n)
                    {
                        $value = get_option($n['post'] . '_slug');
                        echo '<input type="text" value="' . esc_attr($value) . '" name="' . $n['post'] . '_slug' . '" id="' . $n['post'] . '_slug' . '" class="regular-text" placeholder="' . $n['slug'] . '"/>';
                        
                    }, 'permalink', 'optional');
                });
                
            }
            
            
        }
    }
}

function easy_wp_support_functions_slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // trim
    $text = trim($text, '-');
    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // lowercase
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

function easy_wp_support_functions_greeklish($Name)
{
    $greek   = array(
        'α',
        'ά',
        'Ά',
        'Α',
        'β',
        'Β',
        'γ',
        'Γ',
        'δ',
        'Δ',
        'ε',
        'έ',
        'Ε',
        'Έ',
        'ζ',
        'Ζ',
        'η',
        'ή',
        'Η',
        'θ',
        'Θ',
        'ι',
        'ί',
        'ϊ',
        'ΐ',
        'Ι',
        'Ί',
        'κ',
        'Κ',
        'λ',
        'Λ',
        'μ',
        'Μ',
        'ν',
        'Ν',
        'ξ',
        'Ξ',
        'ο',
        'ό',
        'Ο',
        'Ό',
        'π',
        'Π',
        'ρ',
        'Ρ',
        'σ',
        'ς',
        'Σ',
        'τ',
        'Τ',
        'υ',
        'ύ',
        'Υ',
        'Ύ',
        'φ',
        'Φ',
        'χ',
        'Χ',
        'ψ',
        'Ψ',
        'ω',
        'ώ',
        'Ω',
        'Ώ',
        ' ',
        "'",
        "'",
        ','
    );
    $english = array(
        'a',
        'a',
        'A',
        'A',
        'b',
        'B',
        'g',
        'G',
        'd',
        'D',
        'e',
        'e',
        'E',
        'E',
        'z',
        'Z',
        'i',
        'i',
        'I',
        'th',
        'Th',
        'i',
        'i',
        'i',
        'i',
        'I',
        'I',
        'k',
        'K',
        'l',
        'L',
        'm',
        'M',
        'n',
        'N',
        'x',
        'X',
        'o',
        'o',
        'O',
        'O',
        'p',
        'P',
        'r',
        'R',
        's',
        's',
        'S',
        't',
        'T',
        'u',
        'u',
        'Y',
        'Y',
        'f',
        'F',
        'ch',
        'Ch',
        'ps',
        'Ps',
        'o',
        'o',
        'O',
        'O',
        '-',
        '-',
        '-',
        '-'
    );
    $string  = str_replace($greek, $english, $Name);
    return $string;
}



add_action('admin_init', 'easy_wp_support_yoast_exlude_myuser');
function easy_wp_support_yoast_exlude_myuser()
{
    $user_flag = tutorials_users_func('check_cur_role');
    if ($user_flag === 1) {
        add_action('in_admin_footer', 'easy_wp_support_help');
        
        /*load dynamic the scripts*/
        $path = plugin_dir_path(__FILE__) . '/scripts/';
        /*check which dynamic scripts should be loaded*/
        if (file_exists($path)) {
            $paths = array(
                'js',
                'css'
            );
            foreach ($paths as $kk) {
                $check = glob($path . '*.' . $kk);
                if (!empty($check)) {
                    
                    foreach (glob($path . '*.' . $kk) as $filename) {
                        switch ($kk) {
                            case 'js':
                                wp_enqueue_script('easy-wp-support-' . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), array(), true);
                                break;
                            default:
                                wp_enqueue_style('easy-wp-support-' . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), '', 'all');
                                break;
                        }
                    }
                    
                }
            }
            
        }
    }
}
