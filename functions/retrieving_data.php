<?php
//handler for ajax request
add_action("wp_ajax_send_json_data", "send_json_data");
add_action("wp_ajax_nopriv_send_json_data", "send_json_data");

function send_json_data(){

    $selected_terms = null;
    $result = null;
    $terms_query = null;
    $sel_cat = null;
    $distance_unit = 111.045;

    $user_latitude = $_POST['user_latitude'];
    $user_longitude = $_POST['user_longitude'];
    $radius = $_POST['radius'];

    $lng_between_min = $user_longitude - $radius / abs(cos($user_latitude * M_PI / 180)*$distance_unit);
    $lng_between_max = $user_longitude + $radius / abs(cos($user_latitude * M_PI / 180)*$distance_unit);

    $lat_between_min = $user_latitude - ($radius / $distance_unit);
    $lat_between_max = $user_latitude + ($radius / $distance_unit);

    $args = array(
        'post_type' => 'location',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'lat_solo',
                'value' => array((float)$lat_between_min*1000,(float)$lat_between_max*1000),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ),
            array(
                'key' => 'lng_solo',
                'value' => array((float)$lng_between_min*1000,(float)$lng_between_max*1000),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            )

        ),
        'posts_per_page' => -1
    );
    if (isset($_POST['selected_terms']) && ($_POST['selected_terms'] != null)){  //add terms(product line) to request, if selected
        $selected_terms = $_POST['selected_terms'];
        for ($i=0; $i<count($selected_terms); $i++) {
            $terms_query[] = $selected_terms[$i];
        }

        $args +=   array (
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_line',
                    'relation' => 'OR',
                    'field'    => 'id',
                    'terms'    => $selected_terms,
                    'operator' => 'IN'
                )
            ),
        );

    }

    if (isset($_POST['selected_location_category']) && ($_POST['selected_location_category'] != null)){ //add brand to request, if selected
        $sel_cat = $_POST['selected_location_category'];

        $args +=   array (
            'meta_key'   => 'select',
            'meta_value' => $sel_cat,
        );

    }

    $result = get_result($args,$user_latitude,$user_longitude,$radius);

    wp_reset_postdata();
    wp_send_json($result);
}

function get_data_on_load($selected_terms, $sel_cat,$user_latitude,$user_longitude,$radius){ //get data on load page

    $distance_unit = 111.045;
    $lng_between_min = $user_longitude - $radius / abs(cos($user_latitude * M_PI / 180)*$distance_unit);
    $lng_between_max = $user_longitude + $radius / abs(cos($user_latitude * M_PI / 180)*$distance_unit);

    $lat_between_min = $user_latitude - ($radius / $distance_unit);
    $lat_between_max = $user_latitude + ($radius / $distance_unit);

    $args = array(
        'post_type' => 'location',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'lat_solo',
                'value' => array((float)$lat_between_min*1000,(float)$lat_between_max*1000),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ),
            array(
                'key' => 'lng_solo',
                'value' => array((float)$lng_between_min*1000,(float)$lng_between_max*1000),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            )

        ),
        'posts_per_page' => -1
    );

    if ($sel_cat != ''){
        $terms_query = explode(',',$sel_cat);
        $args +=   array (
            'meta_key'   => 'select',
            'meta_value' => $terms_query,
        );
    }

    if ($selected_terms != ''){
        $selected_terms = explode(',', $selected_terms);
        $args +=   array (
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_line',
                    'relation' => 'OR',
                    'field'    => 'id',
                    'terms'    => $selected_terms,
                    'operator' => 'IN'
                )
            ),
        );
    }

    $result = get_result($args,$user_latitude,$user_longitude,$radius);
    wp_reset_postdata();
    return($result);

}

function get_result($args,$user_latitude,$user_longitude,$radius){ //send query and formatting output data

    $query = new WP_Query;
    $posts = $query->query($args);

    $user_latitude = ($user_latitude * M_PI) / 180;
    $user_longitude = ($user_longitude * M_PI )/ 180;

    $args_posts = $args = array(
        'post_type' => 'location_category',
        'posts_per_page' => -1
    );

    $query_locations = new WP_Query;
    $all_posts_location = $query_locations->query($args_posts);

    foreach ($all_posts_location as $post_locations){            // location category have field: icon-marker and image, used in balloon
        $result = null;
        $icon = null;                                            // get them once, that would speed up the time of the script.
        $icon = get_field('icon', $post_locations->ID);
        if ($icon) {
            $size = 'thumbnail'; // icon image size used as marker on maps
            $thumb_icon = $icon['sizes'][$size]; // icon image url
        }

        $image = null;
        $image = get_field('image', $post_locations->ID);
        if ($image) {
            $size = 'thumbnail'; // image size used as image in balloon
            $thumb_image = $image['sizes'][$size]; // image url
        }
        $result_image[$post_locations->ID] = array($thumb_icon,$thumb_image );

    }

    foreach ($posts as $post) {

        $post_meta = get_post_meta($post->ID);

        $db_latitude = $post_meta['lat_solo']['0']/1000;
        $db_longitude = $post_meta['lng_solo']['0']/1000;

        $db_latitude = ($db_latitude * M_PI) / 180;
        $db_longitude = ($db_longitude * M_PI) / 180;

        $dist_km = 6373 * acos( abs(sin($db_latitude)*sin($user_latitude) + cos($db_latitude)*cos($user_latitude)*cos( abs($db_longitude - $user_longitude) ) ) );
        if ($dist_km <= $radius) {

            $address_title = $post_meta['address-solo']['0'];
            $address_lat = $post_meta['lat_solo']['0']/1000;
            $address_lng = $post_meta['lng_solo']['0']/1000;

            $phone_1 = $post_meta['phone_1']['0'];
            $note = $post_meta['note']['0'];

            $terms = null;
            $terms_array = get_the_terms($post->ID, 'product_line');

            if ($terms_array != null ){
                foreach ($terms_array as $term) {
                    $terms[] = $term->name;
                }
            }

            $phone_2 = $post_meta['phone_2']['0'];

            $brand_obj = get_post($post_meta['select']['0']);
            $brand = $brand_obj->post_title;

            $thumb_icon = $result_image[$brand_obj->ID][0];
            $thumb_image = $result_image[$brand_obj->ID][1];

            $result['address'][] = array(
                $address_title,
                $address_lat,
                $address_lng,
                $thumb_icon,
                $phone_1,
                $note,
                $terms,
                $brand,
                $thumb_image,
                $phone_2
            );
        }
    }
    return($result);
}

add_action( 'added_post_meta', 'add_address_solo_and_brand_to_location', 111, 4 ); //add meta field to location (need to search in admin area->location(all))
add_action( 'updated_post_meta','add_address_solo_and_brand_to_location', 111, 4 ); //add meta field to location (need to search in admin area->location(all))
function add_address_solo_and_brand_to_location($meta_id, $post_id, $meta_key, $meta_value){

    if (($meta_key != 'address') && ($meta_key != 'select')) return;

    if ($meta_key == 'select'){
        $brand_id = get_post_meta($post_id, 'select', true);
        $brand = get_the_title($brand_id);
        update_post_meta($post_id, 'brand-solo', $brand);

        $args = array (
            'ID' => $post_id,
            'post_content' => $brand
        );
        wp_update_post($args);
    }

    if ($meta_key == 'address'){
        $meta_address = get_field('address',$post_id);
        update_post_meta($post_id, 'lat_solo', 1000*$meta_address['lat']);
        update_post_meta($post_id, 'lng_solo', 1000*$meta_address['lng']);
        update_post_meta($post_id, 'address-solo', $meta_address['address']);

        $args = array (
            'ID' => $post_id,
            'post_title' => $meta_address['address'],
        );
        wp_update_post($args);
    }
}