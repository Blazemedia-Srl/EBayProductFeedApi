<?php

namespace Blazemedia\EbayProductFeedApi;

use Exception;
use GuzzleHttp\Client;
use Blazemedia\EbayProductFeedApi\Token;

class ApiClient {

    use OAuth;

    
    private static ?ApiClient $instance = null; // $istance is null or ApiClient

    private Client $httpClient;
        
    public Token $token;
    
    private $api = 'https://api.ebay.com/';

    private int $categoryId;

    

    private function __construct( int $categoryId ) {

        $this->categoryId = $categoryId;

        /// istanzia il client 
        /// e imposta i valori di base delle chiamate 
        $this->httpClient = new Client([
            
            'base_uri' => $this->api,
            'verify'   => false, // Disable validation entirely (don't do this!).            
        ]);

        /// effettua il login all'api e rende disponibile
        /// il token all'istanza
        $this->token = $this->getAuthToken( $this->httpClient, 'buy.item.feed' );
    }


    /**
     * Restituisce l'array dei file da scaricare
     *
     * @param integer $lookBackDays - How many days ago to start gathering offers, defaults to 1
     * @return array
     */
    function GetFiles( int $lookBackDays = 1 ) : array { 

        $apiTokenRequestCall = 'buy/feed/v1/file';

        $lookBackSeconds = $lookBackDays * 1440;
    
        /// Effettua la chiamata
        $response = $this->httpClient->get( $apiTokenRequestCall, [

            'headers' => [

                /// indica che il marketplace è quello italiano
                'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',
                /// Accede con il token oauth 2 
                'Authorization' => "Bearer {$this->token->token}"                  
            ],

            'query' => [ 
                /// tipo di feed da scaricare
                'feed_type_id' => 'PRODUCT_FEED',

                /// categorie
                'category_ids' => $this->categoryId,
                
                /*[
                    "key" => "category_ids",
                    "value" =>  "58058,3187",
                    "description" =>  "L1 categories; csv",
                    "disabled" =>  true               
                ],*/
                [
                    "key"   =>  "look_back",
					"value" =>  $lookBackSeconds,
                    "description" =>  "Minutes; 1440 = 24h",
					"disabled" =>  true
                ],
                [
                    "key" =>  "Range",
					"value" => "bytes=50000-3071018"
                ]
                
            ]
        ]);

        if( empty($response) ) return [];

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return [];

        /// la trasforma in array associativo
        $data = array_map( fn( $file ) => $file->fileId , json_decode( $string_data) ->fileMetadata);
    
        return $data;
    } 


    /**
     * Scarica il file 
     *
     * @param string $file
     * @param string $base_path
     * @return int $bytes number of downloaded bytes
     */
    function download( string $file, string $base_path = './' ) : int {

        $apiTokenRequestCall = "buy/feed/v1/file/{$file}/download";
    
        /// Effettua la chiamata
        $response = $this->httpClient->get( $apiTokenRequestCall, [

            'headers' => [

                /// indica che il marketplace è quello italiano
                'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',
                /// Accede con il token oauth 2 
                'Authorization' => "Bearer {$this->token->token}",
                [
                    'key'   => 'Range',
                    'value' => 'bytes=0-93071018',
                ],
                [
                    'key'   => "Accept",
                    'value' =>  "application/octet-stream",
                    "disabled"=> true
                ],
                [
                    'key'   => "Content-Type",
                    'value' => "application/octet-stream",
                    "disabled"=> true
                ],
            ],

        ]);

        if( empty($response) ) return '';

        /// prende lo stream dati come stringa
        $data = $response->getBody()->getContents();

        //var_dump($data); die;

        /// lo scrive su un file
        //$bytes = file_put_contents( "{$base_path}{$file}", $data );

        /// prova ad aprire il file in scrittura
        $file = fopen( "{$base_path}{$file}", 'w');

        $bytes = fwrite( $file, $data, strlen( $data ) );
        fclose( $file ); 

        return $bytes;
        
    }
    
    public static function getInstance( int $categoryId) : ApiClient {

        if( self::$instance === null ) {

            self::$instance = new self( $categoryId );
        }

        return self::$instance;
    }

}