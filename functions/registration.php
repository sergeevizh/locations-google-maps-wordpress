<?php
//registration custom post type, taxonomy

add_action('init', 'init_cp_sale');
register_activation_hook( __FILE__, 'cp_sale_activation' );

function init_cp_sale(){
    location_post_type_registration();
    location_category_post_type_registration();
    registration_taxonomy();
}

function cp_sale_activation() { //hook for components that require activation
    location_post_type_registration();
    location_category_post_type_registration();
    registration_taxonomy();
    flush_rewrite_rules(); //reset rewrite rules to open the URL as follows
}

function location_post_type_registration(){

    $labels = array(
        'name' => 'Locations',
        'singular_name' => 'Location',
        'add_new' => 'add new',
        'add_new_item' => 'add new location',
        'edit_item' => 'edit location',
        'new_item' => 'new location',
        'view_item' => 'view location',
        'search_items' => 'search location',
        'not_found' => 'not found',
        'not_found_in_trash' => 'not found in trash',
        'parent_item_colon' => 'parent item',
        'menu_name' => 'Locations'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=location_category',
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('')
    );

    register_post_type( 'location', $args );
}

function location_category_post_type_registration(){

    $labels = array(
        'name' => 'Location categories',
        'singular_name' => 'Location category',
        'add_new' => 'add new',
        'add_new_item' => 'add new location category',
        'edit_item' => 'edit location category',
        'new_item' => 'new location category',
        'view_item' => 'view location category',
        'search_items' => 'search location category',
        'not_found' => 'not found',
        'not_found_in_trash' => 'not found in trash',
        'parent_item_colon' => 'parent item',
        'menu_name' => 'Location Categories',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_icon' => 'dashicons-location',
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    );

    register_post_type( 'location_category', $args );
}

function registration_taxonomy(){

    $labels_taxonomy = array(
        'name' => _x( 'Product lines', 'taxonomy general name' ),
        'singular_name' => _x( 'Product line', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search product lines' ),
        'popular_items' => __( 'Popular product lines' ),
        'all_items' => __( 'All product lines' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit product line' ),
        'update_item' => __( 'Update product line' ),
        'add_new_item' => __( 'Add product line' ),
        'new_item_name' => __( 'New product line' ),
        'separate_items_with_commas' => __( 'Separate product lines with commas' ),
        'add_or_remove_items' => __( 'Add or remove product lines' ),
        'choose_from_most_used' => __( 'Choose from the most used product lines' ),
        'menu_name' => __( 'Product line types'),
    );

    register_taxonomy('product_line', array('location_category','location'),array(
        'hierarchical' => true,
        'labels' => $labels_taxonomy,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'product_line' ),

    ));
}

//rename admin menu items
function edit_admin_menus() {
    global $menu;
    $menu[26][0] = 'Store locations';
}
add_action( 'admin_menu', 'edit_admin_menus' );

//reordering submenu Store locations
function custom_menu_order() {

    global $submenu;
    $loc_cat = $submenu['edit.php?post_type=location_category'][5];
    $product_linse = $submenu['edit.php?post_type=location_category'][15];
    $submenu['edit.php?post_type=location_category'][5] = $submenu['edit.php?post_type=location_category'][16];
    $submenu['edit.php?post_type=location_category'][15] = $loc_cat;
    $submenu['edit.php?post_type=location_category'][16] = $product_linse;
    if (isset ($submenu['edit.php?post_type=location_category'][10])){
        unset ($submenu['edit.php?post_type=location_category'][10]);
    }
}

add_filter('custom_menu_order', 'custom_menu_order'); // Activate custom_menu_order
add_filter('menu_order', 'custom_menu_order');