<?php

namespace Blazemedia\EBayProductFeedApi;

class Client {

    function __construct() {

        /// effettua il login all'api

        

    }


    /**
     * Returns the data ( JSON o CSC ?) for the last 24h
     * 
     */
    function GetFeed( $category_ids ) { 

        $url = "https://api.ebay.com/buy/feed/v1/file?category_ids={$category_ids}&feed_type_id=PRODUCT_FEED";
        
        // host
    } 



}