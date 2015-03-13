<?php
global $cp_sale_config;

$cp_sale_config ['address_by_default']    = '38.628644 , -98.846407';   // default address
$cp_sale_config ['radius_by_default']     = 1000;                       // default radius
$cp_sale_config ['zoom_by_default']       = 4;                          // zoom map on load page
$cp_sale_config ['default_balloon_width'] = 250;                        // balloon width in px
$cp_sale_config ['default_map_height']    = 600;                        // map height in px
$cp_sale_config ['default_marker_size']   = '26 , 32';                  // marker size in px
$cp_sale_config ['default_marker_origin'] = '0 , 0';                    // marker origin in px
$cp_sale_config ['default_marker_anchor'] = "13 , 32";                  // marker anchor in px

$cp_sale_config ['radius_select'][0][0] = "within 10 miles";            // text for select radius
$cp_sale_config ['radius_select'][0][1] = 17;                          // value for radius

$cp_sale_config ['radius_select'][1][0] = "within 20 miles";
$cp_sale_config ['radius_select'][1][1] = 33;

$cp_sale_config ['radius_select'][2][0] = "within 35 miles";
$cp_sale_config ['radius_select'][2][1] = 57;

$cp_sale_config ['radius_select'][3][0] = "within 50 miles";
$cp_sale_config ['radius_select'][3][1] = 81;

$cp_sale_config ['radius_select'][4][0] = "within 100 miles";
$cp_sale_config ['radius_select'][4][1] = 161;
