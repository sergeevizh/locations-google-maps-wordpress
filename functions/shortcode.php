<?php

add_shortcode('cp-sale-points', 'gmaps_locations');

function gmaps_locations(){

    global $post, $cp_sale_config;
    ob_start();
    wp_enqueue_style('cp-sale-points', plugin_dir_url(__FILE__) . '../style.css');
    wp_enqueue_script('googlemap', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=geometry&sensor=false', array(), false, true);

    $posts = get_posts( array(
        'offset'          => 0,
        'orderby'         => 'title',
        'order'           => 'ASC',
        'post_type'       => 'location_category',
        'post_status'     => 'publish',
        'numberposts'     => -1
    ) );

    $term_args = array(
        'orderby'       => 'name',
        'order'         => 'ASC',
        'hide_empty'    => true,
        'fields'        => 'all',
        'hierarchical'  => true,
        'child_of'      => 0,
        'pad_counts'    => false,
        'cache_domain'  => 'core'
    );

    if ( isset($_GET['radius']) ) { // data for  drawing markers on load page

        if (isset($_GET['selected_terms'])) {
            $selected_terms = $_GET['selected_terms'];
        } else {
            $selected_terms = '';
        }

        if (isset($_GET['selected_location_category'])) {
            $sel_cat = $_GET['selected_location_category'];
        } else {
            $sel_cat = '';
        }

        $user_latitude = $_GET['user_latitude'];
        $user_longitude = $_GET['user_longitude'];
        $radius = $_GET['radius'];
        $result_on_load = get_data_on_load($selected_terms, $sel_cat,$user_latitude,$user_longitude,$radius);
    }
    ?>
    <div class="cp-sale-points">
        <form id="form-send-data">
            <div>Find a story near you</div><hr />
            <div>address: <input type="text" id="address" value="<?php if (isset($_GET['address'])) echo $_GET['address'];?>">

                <select id="radius">
                <?php foreach($cp_sale_config ['radius_select'] as $rad_select):?>
                    <option <?php if (isset ($_GET['radius']) && $_GET['radius'] == $rad_select[1]) echo 'selected="selected"';?>value="<?php echo $rad_select[1];?>"><?php echo $rad_select[0]; ?></option>
                <?php endforeach?>
                </select>
            </div>
            <hr />
            <div>
                Search in (brand line):
                <div id="selected-location-category">
                    <?php foreach ($posts as $post):?>
                        <?php $image = null;
                        $image = get_field('image', $post->ID);
                        if ($image) {
                            $size = 'thumbnail'; // image size used as image in balloon
                            $thumb_image = $image['sizes'][$size]; // image url
                        }?>
                        <div>
                            <input type="checkbox" checked="checked" id="<?php echo $post->ID;?>">
                            <label for="<?php echo $post->ID;?>"><img src="<?php echo $thumb_image?>"></label>
                        </div>
                    <?php endforeach ?>
                </div>
                Product line:
                <div id="selected-terms">
                    <?php $product_line_terms = get_terms( 'product_line', $term_args );?>
                        <?php foreach( $product_line_terms as $term ):?>
                            <div>
                                <input type="checkbox" checked="checked" id="<?php echo ($term->term_id);?>">
                                <label for="<?php echo ($term->term_id);?>"><?php echo ($term->name);?></label>
                            </div>
                        <?php endforeach ?>
                </div>
            </div>
            <div>
                <input type="button" value="find store" id="find_store">
            </div>
        </form>
    </div>
    <script>

        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";                                      // path to admin-ajax.php

        jQuery(document).ready(function () {                                                             //main function

            var address_default = '<?php echo $cp_sale_config['address_by_default'];?>'                                      //address by default
            geocoder = new google.maps.Geocoder();

            var mapOptions = {                                                                          //google map options
                zoom: <?php echo $cp_sale_config['zoom_by_default']; ?>,
                center: new google.maps.LatLng(<?php echo $cp_sale_config['address_by_default'];?>)
            };

            var map = new google.maps.Map(document.getElementById('map-canvas'),mapOptions);                //set map at block div with id="map-canvas"

            //------------------------------------------------------------------------------------------ set markers on load page
            <?php if ( isset($_GET['radius'])) :?>
            var address_on_load = <?php echo json_encode($result_on_load['address']); ?>;
            var response_address = address_on_load;                                        //data from ajax handler
            var address = '<?php if (isset ($_GET['address'])) {echo $_GET['address'];} else echo ' '; ?>';                          //GET  address
            var radius = '<?php if (isset($_GET['radius'])) {echo $_GET['radius']*1000;} else echo ''; ?>';    //GET radius
            if(response_address != null) {
                var drawing_markers = [];
                for (var i = 0; i < response_address.length; i++) {
                    drawing_markers.push(response_address[i]);
                }
                setMarkers(map, drawing_markers);                               //drawing markers
            }
            <?php endif;  ?>
            //--------------------------------------------------------------------------------------------- change history state
            jQuery(window).bind("popstate", function() {
                if (getUrlVars() != false) {

                    jQuery('.cp-message').remove();
                    var url_vars = getUrlVars();
                    var data = {};                                                                         //sending ajax data

                    var user_latitude = decodeURIComponent(url_vars.user_latitude);
                    var user_longitude = decodeURIComponent(url_vars.user_longitude);
                    var radius = url_vars.radius;

                    if (typeof (url_vars.selected_terms) != 'undefined'){
                        var decoded_selected_terms = decodeURIComponent(url_vars.selected_terms);
                        var selected_terms = decoded_selected_terms.split(',');    //get from url terms (taxonomy product_line)

                        data['selected_terms'] = selected_terms;
                    }

                    if (typeof (url_vars.selected_location_category) != 'undefined') {
                        var selected_location_category = url_vars.selected_location_category.split(',');
                        data['selected_location_category'] = selected_location_category;
                    }

                    if (typeof (url_vars.address) != 'undefined') {
                        var address = decodeURIComponent(url_vars.address);                          //get from url address
                    } else var address = address_default;

                    data['user_latitude'] = user_latitude;
                    data['user_longitude'] = user_longitude;
                    data['radius'] = radius;
                    data['action'] = 'send_json_data';

                    //get from url radius in format google maps
                    userAddressAndAjaxHandler(data, address, radius, map, user_latitude, user_longitude);
                }else{
                    DeleteMarkers();
                    jQuery('div.cp-sale-points').append('<div class="cp-message">No results were found</div>');
                }
            });

            //--------------------------------------------------------------------------------------------- handler click event

            jQuery("#find_store").bind('click', function () { //submit on click "find store"

                if (jQuery('#address').val() == ''){ //check the address field is filled
                    jQuery('#address').addClass('cp-error');
                    return;
                }
                jQuery('.cp-message').remove();
                findStore(map);
            });

            jQuery('#form-send-data').keydown(function(e){ //submit when press enter

                if(e.which ==13){
                    e.preventDefault();
                    jQuery('.cp-message').remove();
                    if (jQuery('#address').val() == ''){
                        jQuery('#address').addClass('cp-error');
                    }else{
                        findStore(map);
                    }
                }
            });

            jQuery('#address').bind('focus change', function() {
                jQuery('#address').removeClass('cp-error');
            });

        });

        function userAddressAndAjaxHandler(data, address, radius, map, user_latitude, user_longitude){

            jQuery.post(ajaxurl, data, function(response) {                                         //ajax request, method post

                if (response == null){                                                              //nothing to show
                    jQuery('div.cp-sale-points').append('<div class="cp-message">No results were found</div>');
                    DeleteMarkers();
                } else {                                                                             // if have data to show

                    DeleteMarkers();                                                         //delete current marker
                    var response_address = response.address;                                        //data from ajax handler
                    var drawing_markers =[];

                    for (var i=0; i<response_address.length; i++) {
                        drawing_markers.push( response_address[i]) ;
                    }

                    if (drawing_markers != '') {   //centering the map if there are results

                        var markersBounds = new google.maps.LatLngBounds(); // Область показа маркеров

                        for (var i = 0; i < drawing_markers.length; i++) {

                            var markerPosition = new google.maps.LatLng(drawing_markers[i][1], drawing_markers[i][2]);
                            markersBounds.extend(markerPosition); // Добавляем координаты маркера в область
                        }
                        map.setCenter(markersBounds.getCenter(), map.fitBounds(markersBounds)); // Центрируем и масштабируем карту
                    }
                    setMarkers(map, drawing_markers);                               //drawing markers
                }
            });
        }

        var markers = [];

        function findStore(map){

            var selected_terms = [];                                        //get selected terms (taxonomy product_line)
            jQuery('#selected-terms input:checked').each(function() {
                selected_terms.push(this.id);
            });

            var selected_location_category = [];
            jQuery('#selected-location-category input:checked').each(function() {
                selected_location_category.push(this.id);
            });
            console.log(selected_location_category)
            var address = jQuery('#address').val();                     //get input address
            var radius_km = jQuery('#radius').val();           //get radius in km
            var radius = parseInt(jQuery('#radius').val(), 10)*1000; //get radius in format google maps

            geocoder.geocode( { 'address': address}, function(results, status){ //google handler address input
                if (status == google.maps.GeocoderStatus.OK){

                    var user_latitude = results[0].geometry.location.k;
                    var user_longitude = results[0].geometry.location.D;

                    var data = {                //sending ajax data

                        user_latitude: user_latitude,
                        user_longitude: user_longitude,
                        radius: radius_km,
                        selected_terms: selected_terms,
                        selected_location_category: selected_location_category,
                        action: 'send_json_data'
                    };

                    userAddressAndAjaxHandler(data, address, radius, map, user_latitude, user_longitude);

                    var current_url = window.location.href.split("?")[0];
                    var current_url = current_url+'?radius='+radius_km;

                    if (address !='') current_url = current_url + '&address='+address;

                    if (selected_terms.length > 0) current_url = current_url + '&selected_terms='+selected_terms;
                    if (selected_location_category.length > 0) current_url = current_url + '&selected_location_category='+selected_location_category;

                    current_url = current_url + '&user_latitude='+user_latitude;
                    current_url = current_url + '&user_longitude='+user_longitude;

                    history.pushState(null,null , current_url);  // save html 5 history state
                }
                else{
                    DeleteMarkers();
                    jQuery('div.cp-sale-points').append('<div class="cp-message">Address not find</div>');
                    console.log('Geocode was not successful for the following reason: ' + status);
                    var current_url = window.location.href.split("?")[0];
                    history.pushState(null,null , current_url);
                }
            });
        }

        function setMarkers(map, locations) {                //function to set markers and balloons

            if (locations != null) {
                for (var i = 0; i < locations.length; i++) {

                    var location = locations[i];
                    var myLatLng = new google.maps.LatLng(location[1], location[2]);

                    var image = {                                          //  marker icon options
                        url: location[3],
                        // This marker is 20 pixels wide by 32 pixels tall.
                        size: new google.maps.Size(<?php echo $cp_sale_config['default_marker_size'];?>),
                        // The origin for this image is 0,0.
                        origin: new google.maps.Point(<?php echo $cp_sale_config['default_marker_origin'];?>),
                        // The anchor for this image is the base of the flagpole at 0,32.
                        anchor: new google.maps.Point(<?php echo $cp_sale_config['default_marker_anchor'];?>)
                    };

                    var address = location[0];
                    var image_url = location[8];
                    var phone_1 = location[4];
                    var phone_2 = location [9];
                    var note = location[5];
                    var product_line = location [6];
                    var brand = location[7];

                    var contentString = '<img src="' + image_url + '">' +      //balloon content
                        '<div>Address: ' + address + '</div>';

                    if (brand != null) {
                        contentString = contentString + '<div>Brand: ' + brand + '</div>';
                    }
                    if (product_line != null) {
                        contentString = contentString + '<div>Product line: ' + product_line + '</div>';
                    }
                    if (phone_1 != false) {
                        contentString = contentString + '<div>Phone: ' + phone_1 + '</div>';
                    }
                    if (phone_2 != false) {
                        contentString = contentString + '<div>Phone 2: ' + phone_2 + '</div>';
                    }
                    if (note != false) {
                        contentString = contentString + '<div>Note: ' + note + '</div>';
                    }

                    var infowindow = new google.maps.InfoWindow({ //balloon options
                        content: contentString,
                        maxWidth: <?php echo $cp_sale_config['default_balloon_width'];?> //balloon max width set
                    });

                    var marker = new google.maps.Marker({ //marker options
                        position: myLatLng,
                        map: map,
                        icon: image,
                        title: location[0],
                        optimized: false
                    });

                    function infoCallback(infowindow, marker) {  //function to prevent Closures (https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Closures#Creating_closures_in_loops.3a_A_common_mistake)
                        return function () {
                            infowindow.open(map, marker);
                        };
                    }

                    infowindow.setContent(contentString); //set balloon content
                    google.maps.event.addListener(marker, 'click', infoCallback(infowindow, marker)); //show balloon on click marker
                    markers.push(marker);    //save marker in array of markers, this need to delete all markers

                }
            }
        }

        //--------------------------------------------------------------------------------------------------

        function DeleteMarkers() {    //Loop through all the markers and remove
            for (var i = 0; i < markers.length; i++) {
                markers[i].setMap(null);
            }
            markers = [];
        };

        function getUrlVars() {                     //if isset url get parametrs save him in array, else return false

            if (window.location.href.indexOf('?') == -1) return false;
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');

            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }
    </script>
    <div id="map-canvas" style="height: <?php echo $cp_sale_config['default_map_height']; ?>px;"></div>
    <?php
    return ob_get_clean();
}
