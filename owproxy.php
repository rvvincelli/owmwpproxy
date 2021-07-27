<?php
/**
* Plugin Name: OpenWeatherMap proxy
* Description: A simple WP-aware endpoint for OpenWeatherMap
* Version: 1.0
* Author: R. Vincelli
* Author URI: https://github.com/rvvincelli
**/

/**
 * CONFIG
 * http://wpsettingsapi.jeroensormani.com/
 */

use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Forecast;
use Http\Factory\Guzzle\RequestFactory;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

/**
 * PREAMBLE
 */
defined('ABSPATH') or die();
if (!defined('OWMPROXY_PATH')) {
    define('OWMPROXY_PATH', __DIR__);
}
require_once OWMPROXY_PATH . '/vendor/autoload.php';

/**
 * INCLUDES
 */
include_once OWMPROXY_PATH . '/vendor/guzzlehttp/guzzle/src/functions.php';
include_once OWMPROXY_PATH . '/vendor/guzzlehttp/psr7/src/functions.php';
include_once OWMPROXY_PATH . '/vendor/guzzlehttp/promises/src/functions.php';
//
include_once OWMPROXY_PATH . '/Cache.php';

/**
 * CONFIG
 */

function owmp_text_field_0_render(  ) { 

	$options = get_option( 'owmp_settings' );
	?>
	<input type='text' name='owmp_settings[owmp_text_field_0]' value='<?php echo $options['owmp_text_field_0']; ?>'>
	<?php

}


function owmp_text_field_1_render(  ) { 

	$options = get_option( 'owmp_settings' );
	?>
	<input type='text' name='owmp_settings[owmp_text_field_1]' value='<?php echo $options['owmp_text_field_1']; ?>'>
	<?php

}


function owmp_settings_section_callback(  ) { 

	echo __( 'Fill in the desired configuration for the proxy', 'owmp' );

}


function owmp_options_page(  ) { 

		?>
		<form action='options.php' method='post'>

			<h2>OpenWeatherMap Proxy</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

}

function owmp_add_admin_menu(  ) { 

	add_menu_page( 'OpenWeatherMap Proxy', 'OpenWeatherMap Proxy', 'manage_options', 'openweathermap_proxy', 'owmp_options_page' );

}
add_action( 'admin_menu', 'owmp_add_admin_menu' );



function owmp_settings_init(  ) { 

	register_setting( 'pluginPage', 'owmp_settings' );

	add_settings_section(
		'owmp_pluginPage_section', 
		__( 'OWMP configuration', 'owmp' ), 
		'owmp_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'owmp_text_field_0', 
		__( 'OpenWeatherMap REST API key', 'owmp' ), 
		'owmp_text_field_0_render', 
		'pluginPage', 
		'owmp_pluginPage_section' 
	);

	add_settings_field( 
		'owmp_text_field_1', 
		__( 'Location (e.g. Monza,IT)', 'owmp' ), 
		'owmp_text_field_1_render', 
		'pluginPage', 
		'owmp_pluginPage_section' 
	);


}
add_action( 'admin_init', 'owmp_settings_init' );

function get_api_key() {
  $options = get_option( 'owmp_settings' );
  $apiKey = $options['owmp_text_field_0'] ?? null;
  return $apiKey;
}

function get_city() {
  $options = get_option( 'owmp_settings' );
  $city = $options['owmp_text_field_1'] ?? null;
  return $city;
}

function get_owm_instance() {
  $httpRequestFactory = new RequestFactory();
  $httpClient = GuzzleAdapter::createWithConfig([]);
  $apiKey = get_api_key();
  $ttl = 86400;
  $cacheLocation = OWMPROXY_PATH . '/cache';
  $cache = OWMPCache::getInstance($cacheLocation);
  $owm = new OpenWeatherMap($apiKey, $httpClient, $httpRequestFactory, $cache, $ttl);
  return $owm;
}

function get_5_day_daily_forecast() {
  $owm = get_owm_instance();
  $query = get_city();
  $units = 'metric';
  $lang = 'it';
  // this is the max we can afford with a free api key it seems, a longer lookup will give a 401
  // see https://openweathermap.org/price#weather
  $days = 5;
  $forecasts = $owm->getWeatherForecast($query, $units, $lang, '', $days);
  return $forecasts;
}

function get_n_days_in_the_future_forecast(int $n) {
  if ($n >= 0 && $n <= 5) {
    $forecasts = get_5_day_daily_forecast();
    $i = 0;
    foreach ($forecasts as $forecast) {
      if ($n == $i) {
        return $forecast;
      }
      else {
        $i++;
      }
    }
    throw new \UnexpectedValueException("forecasts object contains more elements than expected: $i");
  }
  else {
    throw new \OutOfRangeException("'n' value must be in an int the [0, 5] range, '$n' provided instead");
  }
}

function get_forecast_weather_icon_url(Forecast $forecast) {
  $iconUrl = "https:".$forecast->weather->getIconUrl();
  return $iconUrl;
}

function get_unknown_weather_icon_url() {
  $iconUrl = plugins_url('/assets/unknown_weather.png', __FILE__);
  return $iconUrl;
}

function get_weather_icon_url(int $n) {
  try {
    $forecast = get_n_days_in_the_future_forecast($n);
    $icon_url = get_forecast_weather_icon_url($forecast);
    return $icon_url;
  } catch (\OutOfRangeException $_) {
      return get_unknown_weather_icon_url();
  }
}

/**
 * ENDPOINT
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
 * https://christianflach.de/OpenWeatherMap-PHP-API/docs/apis/weather-forecast
 */

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function prefix_get_forecast_5_daily_icon_url($request) {
  // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
  $n = $request['n'] ?? null;
  if (isset($n) && in_array($n, array('0', '1', '2', '3', '4', '5'))) {
    $iconUrl = get_weather_icon_url(intval($n));
    $headers = array(
      'Cache-Control' => 'public',
      'Expires' => gmdate('D, d M Y H:i:s', strtotime('tomorrow')) . ' GMT'
    );
  }
  else {
    $iconUrl = get_unknown_weather_icon_url();
    $headers = array(
      'Cache-Control' => 'public',
      'Expires' => gmdate('D, d M Y H:i:s', strtotime('tomorrow +6 month')) . ' GMT'
    );
  }
  $response = new WP_HTTP_RESPONSE(array("item" => $iconUrl), 200, $headers);
  return $response;
}

/**
 * We can use this function to contain our arguments for the example product endpoint.
 */
function prefix_get_forecast_5_daily_arguments() {
  $args = array();
  // Here we are registering the schema for the filter argument.
  $args['n'] = array(
      // description should be a human readable description of the argument.
      'description' => esc_html__( 'The n parameter is used to ask for a forecast n days in the future, starting from 0', 'owmp' ),
      // type specifies the type of data that the argument should be.
      'type'        => 'string',
  );
  return $args;
}

/**
* This function is where we register our routes for our example endpoint.
*/
function prefix_register_routes() {
  // register_rest_route() handles more arguments but we are going to stick to the basics for now.
  register_rest_route( 'owmp/v1', '/forecast/5/daily', array(
      // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
      'methods'  => WP_REST_Server::READABLE,
      // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
      'callback' => 'prefix_get_forecast_5_daily_icon_url',
      // These are the arguments we expect in our query.
      'args' => prefix_get_forecast_5_daily_arguments()
  ) );
}

add_action( 'rest_api_init', 'prefix_register_routes' );
