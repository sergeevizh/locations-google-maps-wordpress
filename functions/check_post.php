<?php
//AJAX to validate event before publishing

add_action('admin_enqueue_scripts-post.php', 'cp_load_jquery_js');
add_action('admin_enqueue_scripts-post-new.php', 'cp_load_jquery_js');
function cp_load_jquery_js(){
    global $post;
    if ( $post->post_type == 'location' ) {
        wp_enqueue_script('jquery');
    }
}
add_action( 'admin_enqueue_scripts', 'my_admin_enqueue_scripts' );
function my_admin_enqueue_scripts() {
    if ( 'location' == get_post_type() )
        wp_dequeue_script( 'autosave' );
}

add_action('admin_head-post.php','cp_publish_admin_hook');
add_action('admin_head-post-new.php','cp_publish_admin_hook');
function cp_publish_admin_hook(){
    global $post;
    if ( is_admin() && is_edit_page('new') && $post->post_type == 'location' ){
        ?>
        <script language="javascript" type="text/javascript">

            jQuery(document).ready(function() {

                jQuery('#publish').click(function(event) {

                    if (jQuery('#acf-field-select option:selected').val()!= 'null' && jQuery('.input-address').val().length > 0){
                        event.preventDefault();
                        jQuery('div #message').remove();
                        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
                        var lat = jQuery('.input-lat').val();
                        var lng = jQuery('.input-lng').val();
                        var address = jQuery('.input-address').val();

                        var data = {
                            action: 'cp_pre_submit_validation',
                            lat: lat,
                            lng: lng,
                            address: address
                        };

                        jQuery.post(ajaxurl,data,function(response){
                            if (response.uniq == 'yes'){
                                jQuery("#post").submit();
                            } else {
                                jQuery('h2').append('<div id="message" class="error"><p>This location is already exist on database.</p></div>');
                                //alert ('location have in the database');
                            }
                        })
                    }
                });
            });
        </script>
    <?php
    }
}

function is_edit_page($new_edit = null){
    global $pagenow;
    //make sure we are on the backend
    if (!is_admin()) return false;


    if($new_edit == "edit")
        return in_array( $pagenow, array( 'post.php',  ) );
    elseif($new_edit == "new") //check for new post page
        return in_array( $pagenow, array( 'post-new.php' ) );
    else //check for either new or edit
        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
}

add_action('wp_ajax_cp_pre_submit_validation', 'cp_pre_submit_validation');
function cp_pre_submit_validation() {

    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $address = $_POST['address'];

    $args = array(
        'post_type' => 'location',
        'meta_key'   => 'address-solo',
        'meta_value' => $address,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'lat_solo',
                'value' => (float)$lat*1000,
                'compare' => '='
            ),
            array(
                'key' => 'lng_solo',
                'value' => (float)$lng*1000,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );

    $query = new WP_Query;
    $posts = $query->query($args);

    if (count($posts) > 0){
        $result['uniq'] = 'no';
    } else {
        $result['uniq'] = 'yes';
    }

/*
    $args_adr = array(
        'post_type' => 'location',
        'meta_key'   => 'address-solo',
        'meta_value' => $address,
        'posts_per_page' => -1
    );

    $query_adr = new WP_Query;
    $posts_adr = $query_adr->query($args_adr);

    if (count($posts_adr) > 0){
        $result['uniq'] = 'no';
    } else {
        $result['uniq'] = 'yes';
    }*/

    wp_send_json($result);
}