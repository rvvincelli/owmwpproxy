# OpenWeatherMap Proxy

A simple WordPress plugin to obtain weather information via [OpenWeatherMap](https://openweathermap.org/). This is useful to incorporate weather-related information in any existing WP plugin or widget, leveraging on a modern REST API approach.

A future intention is to make the plugin freely available on the Plugin Directory. To our knowledge it fully respects the [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/). But at the moment we cannot comply with the [submission requirements](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/), most importantly providing a zip which is 10MB or less.
## License

This plugin is offered under GPLv2, just like WordPress itself. You are invited to read the `LICENSE` file.
### External services discharge

This plugin offers an as-is interface to external services. The author is not responsible for any misuse of the plugin with respect to these external services, nor any claims by third parties following from such misuse.

### Non-affiliation

The author is not affiliated with any of the external services the plugin provides an interface to.

## Installation

As mentioned above the plugin is not yet submitted to the Plugins Directory.

For the manual installation, checkout this repository and run `package.sh`; a zip for the plugin upload will be created. Then, fill in the API key and city as specified.

Should you have issues with the zip size, which is about 15 MB, please have a look at [this](https://wordpress.org/plugins/upload-larger-plugins/) plugin.

## Compatibility

This plugin was tested on WP 5.8 with PHP 7.3. The `vendor/` dir was assembled on macOS Catalina with `composer`. No PHP extensions are used.

## Example

All the plugin does is: returning the weather icon for a 0 (today), 1, ..., 5 day forecast of the specified city, querying OWM with the configured key. In case no information is available, a default unknown is returned: this is the case for out of range requests or generic errors.

To try it out:
`curl -k https://www.example.com/site/wp-json/owmp/v1/forecast/5/daily?n=<N>` with `<N>` in `<0..5>`.

For a hint on how to use it from a frontend application, see the `examples` folder, but make sure to use an async request, especially if you are querying the endpoint in multiple points of your page.

## Configuration

At the moment the only configuration you need to provide is an OWM [API key](https://openweathermap.org/api) and the city you are interested in. The only weather query implemented now works with a free key.

## How it works

The plugin is basically a web wrapper around [OpenWeatherMap-PHP-API](https://github.com/cmfcmf/OpenWeatherMap-PHP-Api), serving from a WP-compliant JSON endpoint. 

## Caching

The plugin uses an internal file-based cache for the requests. This cache is stored inside the plugin installation folder. I am unsure if this is a WP-ish choice but it basically means that once the plugin is uninstalled there's nowhere else to clean up, cache is gone too.

An assumption is that this is not likely to cause space issues because for a single configured location and the basic 5-day weather forecast the cache will stay small.

### On the client side

Our endpoint specifies cache directives for the response. The default icon is served as a static asset while the actual 0..5 ones expire at the end of the day: this is the natural caching choice as we are referring to day-long weather queries, and the query result is expected to be stale no earlier than midnight.

Make sure your hosting provider web server honors these caching headers.

## Why this?

What we needed was a customization of an existing commercial plugin, and we wanted to do it properly.

### Way forward

A natural extension of this plugin is offering more endpoints, ideally one per service as offered by OWM.
