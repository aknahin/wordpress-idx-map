function display_property_map_with_filters() {
    global $wpdb;
    
    // Enqueue Google Maps API script
    wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY', [], null, true);

    // Pass ajax_url to the script
    wp_localize_script('google-maps', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);

    // HTML for filters
    $html = '<div id="property-filters" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <label for="bedrooms">Bedrooms:</label>
                <select id="bedrooms">
                    <option value="">Any</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                
                <label for="bathrooms">Bathrooms:</label>
                <select id="bathrooms">
                    <option value="">Any</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                
                <label for="area">Area (sqft):</label>
                <select id="area">
                    <option value="">Any</option>
                    <option value="1000-999999">1000+</option>
                    <option value="2000-999999">2000+</option>
                    <option value="3000-999999">3000+</option>
                </select>
                
                <label for="price">Price:</label>
                <input type="range" id="price" min="0" max="1000000" step="50000" value="1000000">
                <span id="price-value">$1,000,000</span>
                
                <label for="city">City:</label>
                <input type="text" id="city" style="width: auto;">
                
                <button id="reset-filters" style="padding: 5px 10px; font-size: 14px;">Reset Filters</button>
             </div>
             <div id="loading" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 20px; background: rgba(0, 0, 0, 0.6); color: black; font-size: 20px; z-index: 1000;">Loading...</div>';

    // HTML for the map
    $html .= '<div id="map" style="width: 100%; height: 500px;"></div>';

    // JavaScript for Google Maps and filters
    $html .= '<script>
        jQuery(document).ready(function ($) {
            let map;
            let markers = [];
            let infoWindow;

            function initializeMap() {
                const mapOptions = {
                    zoom: 7,
                    center: new google.maps.LatLng(33.448376, -112.074036), // Default center
                };
                map = new google.maps.Map(document.getElementById("map"), mapOptions);
                infoWindow = new google.maps.InfoWindow();
                loadMarkers();
            }

            function loadMarkers(filters = {}) {
                $("#loading").show();
                $.ajax({
                    url: ajax_object.ajax_url,
                    method: "POST",
                    data: {
                        action: "filter_properties",
                        bedrooms: filters.bedrooms,
                        bathrooms: filters.bathrooms,
                        area: filters.area,
                        price: filters.price,
                        city: filters.city,
                    },
                    success: function (response) {
                        clearMarkers();
                        response.forEach(function (property) {
                            const marker = new google.maps.Marker({
                                position: new google.maps.LatLng(property.latitude, property.longitude),
                                map: map,
                                title: property.post_name,
                            });

                            const gallery = $(property.gallery).filter("img")[0];
                            const galleryImage = gallery ? gallery.src : "";
                            const contentString = `
                                <div>
                                    <strong>${property.address}</strong>
                                    <table>
                                        <tr>
                                            <td><img src="${galleryImage}" alt="${property.address}" style="width: 100px; height: 100px;"></td>
                                            <td>
                                                <p>Bedrooms: ${property.bedrooms}</p>
                                                <p>Bathrooms: ${property.bathrooms}</p>
                                                <p>Square Feet: ${property.sqft}</p>
                                                <p>ZIP: ${property.zip}</p>
                                                <p>Price: ${property.price}</p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            `;

                            marker.addListener("mouseover", function () {
                                infoWindow.setContent(contentString);
                                infoWindow.open(map, marker);
                            });

                            marker.addListener("mouseout", function () {
                                setTimeout(() => {
                                    if (!infoWindow.getMap()) {
                                        infoWindow.close();
                                    }
                                }, 100);
                            });

                            markers.push(marker);
                        });
                        $("#loading").hide();
                    },
                });
            }

            function clearMarkers() {
                markers.forEach(function (marker) {
                    marker.setMap(null);
                });
                markers = [];
            }

            $("#property-filters select, #property-filters input").on("change", function () {
                const filters = {
                    bedrooms: $("#bedrooms").val(),
                    bathrooms: $("#bathrooms").val(),
                    area: $("#area").val(),
                    price: $("#price").val(),
                    city: $("#city").val(),
                };
                loadMarkers(filters);
            });

            $("#reset-filters").on("click", function () {
                $("#property-filters select, #property-filters input").val("");
                $("#price-value").text("$1,000,000");
                loadMarkers();
            });

            $("#price").on("input", function () {
                const price = $(this).val();
                const formattedPrice = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(price);
                $("#price-value").text(formattedPrice);
            });

            google.maps.event.addDomListener(window, "load", initializeMap);
        });
    </script>';

    return $html;
}

// Register the shortcode
add_shortcode('idx_property_table', 'display_property_map_with_filters');

// Handle AJAX request to filter properties
add_action('wp_ajax_filter_properties', 'filter_properties');
add_action('wp_ajax_nopriv_filter_properties', 'filter_properties');

function filter_properties() {
    global $wpdb;

    $query = "
        SELECT p.ID, p.post_name, pm1.meta_value AS latitude, pm2.meta_value AS longitude,
               pm3.meta_value AS address, pm4.meta_value AS bedrooms, pm5.meta_value AS bathrooms,
               pm6.meta_value AS sqft, pm7.meta_value AS zip, pm8.meta_value AS gallery, pm9.meta_value AS price
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_listing_latitude'
        LEFT JOIN {$wpdb->prefix}postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_listing_longitude'
        LEFT JOIN {$wpdb->prefix}postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_listing_address'
        LEFT JOIN {$wpdb->prefix}postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_listing_bedrooms'
        LEFT JOIN {$wpdb->prefix}postmeta pm5 ON p.ID = pm5.post_id AND pm5.meta_key = '_listing_bathrooms'
        LEFT JOIN {$wpdb->prefix}postmeta pm6 ON p.ID = pm6.post_id AND pm6.meta_key = '_listing_sqft'
        LEFT JOIN {$wpdb->prefix}postmeta pm7 ON p.ID = pm7.post_id AND pm7.meta_key = '_listing_zip'
        LEFT JOIN {$wpdb->prefix}postmeta pm8 ON p.ID = pm8.post_id AND pm8.meta_key = '_listing_gallery'
        LEFT JOIN {$wpdb->prefix}postmeta pm9 ON p.ID = pm9.post_id AND pm9.meta_key = '_listing_price'
        WHERE p.post_type = 'listing' AND p.post_status = 'publish'
    ";

    // Add filters to the query
    if (!empty($_POST['bedrooms'])) {
        $query .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_listing_bedrooms' AND pm.meta_value >= %d)", $_POST['bedrooms']);
    }
    if (!empty($_POST['bathrooms'])) {
        $query .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_listing_bathrooms' AND pm.meta_value >= %d)", $_POST['bathrooms']);
    }
    if (!empty($_POST['area'])) {
        list($min_area, $max_area) = explode('-', $_POST['area']);
        $query .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_listing_sqft' AND CAST(REPLACE(pm.meta_value, ',', '') AS UNSIGNED) BETWEEN %d AND %d)", $min_area, $max_area);
    }
    if (!empty($_POST['price'])) {
        $price = (int) str_replace(['$', ','], '', $_POST['price']);
$query .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_listing_price' AND CAST(REPLACE(REPLACE(pm.meta_value, ',', ''), '$', '') AS UNSIGNED) <= %d)", $price);
    }
    if (!empty($_POST['city'])) {
        $query .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_listing_city' AND pm.meta_value = %s)", $_POST['city']);
    }

    $properties = $wpdb->get_results($query);

    wp_send_json($properties);
}
