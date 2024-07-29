# WordPress Property Map with Filters

This WordPress plugin displays a Google Map with property listings and allows users to filter properties based on various criteria such as bedrooms, bathrooms, area, price, and city.

## Prerequisites

- Ensure you have the [IDX Broker Platinum](https://wordpress.org/plugins/idx-broker-platinum/) plugin installed and the API connected.

## Installation

1. **Add the Code to Your Theme:**

   Save the following code to your theme's `functions.php` file or create a new file named `idx-map.php` and include it in your theme.

2. **Register the Shortcode:**

   Add the provided PHP code to your theme's `functions.php` file. The code registers a shortcode `[idx_property_map]` that you can use to display the property map.

3. **Include the Shortcode in a Page or Post:**

   Use the shortcode `[idx_property_map]` in any page or post where you want the property map to appear.

## Configuration

### Google Maps API Key

Replace `YOUR_GOOGLE_MAPS_API_KEY` in the following line with your actual Google Maps API key:

```php
wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY', [], null, true);
```
### Map default center

Change map default center and zoom in line
```
const mapOptions = 
    {
        zoom: 7,
        center: new google.maps.LatLng(33.448376, -112.074036), // Default center
    };
```
