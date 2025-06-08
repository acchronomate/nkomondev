<?php

if (!function_exists('getClientIPGeoLocation')) {
    /**
     * @throws JsonException
     */
    function getClientIPGeoLocation() {
        /*
         * By using ip-api.com service, we can get the geolocation of the client IP address.
         * This function returns a json object with the following properties: status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting,query
         * And get on parameters: query (as IP address) and lang like 'http://ip-api.com/json/{query}?lang=fr&fields=...properties...'
         */

        $ip = request()->ip();
        $locale = app()->getLocale();
        $url = "https://ip-api.com/json/{$ip}?lang={$locale}&fields=status,message,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timeZone,offset,currency,isp,org,as,asname,reverse,mobile,proxy,hosting";
        $response = file_get_contents($url);
        if ($response === false) {
            return null; // Handle error appropriately
        }
        $geoData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($geoData['status']) && $geoData['status'] === 'fail') {
            return null; // Handle error appropriately
        }
        return $geoData;
    }
}
