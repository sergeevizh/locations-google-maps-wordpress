<?php
//custom list of all location (address)
//create new column
add_filter('manage_edit-location_columns', 'add_views_column', 4);
function add_views_column( $columns ){
    $columns['brand'] = 'Brand';
    $columns['address'] = 'Address';
    $columns['product_line'] = 'Product lines';
    $columns['edit_location_link'] = 'Edit';
    return $columns;
}

//fill column data
add_filter('manage_location_posts_custom_column', 'fill_views_column', 5, 2);
function fill_views_column($column_name, $post_id) {
    if( ($column_name != 'address') && ($column_name != 'brand') && ($column_name != 'product_line') && ($column_name != 'edit_location_link'))
        return;
    if ($column_name == 'address') {
        $address_all = get_field('address',$post_id);
        $address_title = $address_all['address'];
        echo $address_title;
    }
    if (($column_name == 'brand')){

        $brand_obj = get_field('select', $post_id);
        $brand = $brand_obj->post_title;
        echo $brand;
    }
    if (($column_name == 'product_line')){
        $terms_array =  wp_get_post_terms ($post_id, 'product_line');
        if ( !empty($terms_array) ){
            foreach ($terms_array as $term){
                echo $term->name.'<br />';
            }
        }
    }

    if (($column_name == 'edit_location_link')){
        echo '<a href='.get_edit_post_link( $post_id ).' >Edit</a>';
    }

}

//add column sorting property
add_filter('manage_edit-location_sortable_columns', 'add_views_sortable_column');
function add_views_sortable_column($sortable_columns){
    $sortable_columns['address'] = 'address';
    $sortable_columns['brand'] = 'brand';
    return $sortable_columns;
}

//change query when corting column
add_filter('pre_get_posts', 'add_column_address_request');
function add_column_address_request( $object ){
    if( ($object->get('orderby') != 'address') && ($object->get('orderby') != 'brand') )
        return;
    if ($object->get('orderby') == 'address') {
        $object->set('meta_key', 'address-solo');
        $object->set('orderby', 'meta_value meta_value_num');
    }
    if ($object->get('orderby') == 'brand') {
        $object->set('meta_key', 'brand-solo');
        $object->set('orderby', 'meta_value');
    }
}

//delete columns in location list
add_filter( 'manage_edit-location_columns', 'my_columns_filter', 10, 1 );
function my_columns_filter( $columns ) {
    unset($columns['title']);
    unset($columns['date']);
    return $columns;
}