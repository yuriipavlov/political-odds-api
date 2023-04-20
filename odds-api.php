<?php

/*
Plugin Name: Odds API
Plugin URI: https://example.com/
Description: Provides a JSON API endpoint for odds data.
Version: 1.0
Author: Yurii Pavlov
Author URI: https://example.com/
*/

add_action( 'rest_api_init', 'register_odds_api_endpoint' );

function register_odds_api_endpoint()
{
    register_rest_route( 'odds/v1', '/api', array(
        'methods'  => 'GET',
        'callback' => 'get_odds_api',
    ) );
}

function get_odds_api()
{
    $url      = 'https://viscaweb.com/developers/sia-rp/SIA.html';
    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        return json_encode( $response->get_error_message() );
    }

    $dom = new DOMDocument();
    $dom->loadHTML( $response['body'] );
    $xpath = new DOMXPath( $dom );

    // Extract odds
    $odds         = [];
    $betNameNodes = $xpath->query( "//div[@class='bet-name']" );
    foreach ( $betNameNodes as $betNameNode ) {
        $betName        = $betNameNode->nodeValue;
        $betOptions     = [];
        $betOptionNodes = $xpath->query( ".//div[@class='bet-option']", $betNameNode );
        foreach ( $betOptionNodes as $betOptionNode ) {
            $outcome      = $xpath->query( ".//div[@class='outcome']", $betOptionNode )->item( 0 )->nodeValue;
            $oddsValue    = $xpath->query( ".//div[@class='odds']", $betOptionNode )->item( 0 )->nodeValue;
            $oddsValue    = floatval( preg_replace( "/[^0-9\.]/", '', $oddsValue ) ); // Convert odds value to float
            $betOptions[] = [
                'Outcome' => $outcome,
                'Odds'    => $oddsValue,
            ];
        }
        $odds[] = [
            'BetName'    => $betName,
            'BetOptions' => $betOptions,
        ];
    }

    // Format odds as JSON and return JSON API response
    return json_encode( $odds );
}
