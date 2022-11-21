<?php

namespace Blazemedia\EbayProductFeedApi;

use Exception;
use GuzzleHttp\Client;

class ApiClient {

    private static ?ApiClient $instance = null;

    private Client $httpClient;
        
    public string $token;
    
    private $api = 'https://api.ebay.com/';

    const DEF_CATERGORY_ID = 11450;

    private function __construct() {

        $this->httpClient = new Client([
            
            'base_uri' => $this->api,
            'verify'   => false, // Disable validation entirely (don't do this!).            
        ]);

        /// effettua il login all'api e rende disponibile
        /// il token all'istanza
        $this->token = $this->getAuthToken();

        //        var_dump( $this->token );
    }



    /**
     * Restituisce l'array dei file da scaricare
     *
     * @return array
     */
    function GetFiles() : array { 

        $apiTokenRequestCall = 'buy/feed/v1/file';
    
        /// Effettua la chiamata
        $response = $this->httpClient->get( $apiTokenRequestCall, [

            'headers' => [

                /// indica che il marketplace è quello italiano
                'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',
                /// Accede con il token oauth 2 
                'Authorization' => "Bearer {$this->token}"                  
            ],

            'query' => [ 
                /// tipo di feed da scaricare
                'feed_type_id' => 'PRODUCT_FEED',

                /// categorie
                'category_ids' => ApiClient::DEF_CATERGORY_ID,
                /*
                [
                    "key" => "category_ids",
                    "value" =>  "58058,3187",
                    "description" =>  "L1 categories; csv",
                    "disabled" =>  true               
                ],
                [
                    "key"   =>  "look_back",
					"value" =>  "1440",
                    "description" =>  "Minutes; 1440 = 24h",
					"disabled" =>  false
                ]
                */
            ]
        ]);

        if( empty($response) ) return '';

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return '';

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
                'Authorization' => "Bearer {$this->token}",
                
                'Range' => 'bytes=0-2097152',
            ],

        ]);

        if( empty($response) ) return '';

        /// prende lo stream dati come stringa
        $data = $response->getBody()->getContents();

        /// lo scrive su un file
        $bytes = file_put_contents( "{$base_path}{$file}", $data );

        return $bytes;
    }


    /**
     * Ritorna il token per l'autorizzazione
     *
     * @return string
     */
    protected function getAuthToken() : string {

        /// ottiene il token da file o da chiamata http
        $token = $this->readToken();

        /// persiste il token su file
        $this->writeToken( $token );
        
        return  $token;
    }


    /**
     * Ritorna il token contenuto nel file
     *
     * @return string
     */
    protected function readToken() : string {

        try {

            /// viene recuperato il token dal contenuto del file
            $token = parse_ini_file('./token.ini');

            return trim( $token[ "token" ] );
        
        } catch( Exception $e ) {

            $token = $this->getToken();

            $this->writeToken( $token );

            return $token;
        }         
    }

    
    /**
     * Scrive il token in un file
     *
     * @param string $token
     * @param string $filepath
     * @return void
     */
    protected function writeToken( string $token, string $filepath = './token.ini' ) { 

        /// prova ad aprire il file in scrittura
        $file = fopen( $filepath, 'w');

        if( $file === false ) return; 

        fwrite( $file, "token=\"{$token}\"" );
        fclose( $file ); 
    }

    /**
     * Effettua la chiamata per la generazione del token
     * da utilizzare nelle chiamate
     *
     * @return string
     */
    protected function getToken() { 

        $apiTokenRequestCall = 'identity/v1/oauth2/token';
    
        /// Effettua la chiamata
        $response = $this->httpClient->post( $apiTokenRequestCall, [

            'headers' => [

                'Content-Type' => 'application/x-www-form-urlencoded',

                /// indica che il marketplace è quello italiano
                'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',

                /// basic auth con le credenziali in formato base64
                'Authorization' => 'Basic QmxhemVNZWQtdGVsZWZvbmktUFJELWZlYWEzZDdlNi0yNjAzY2U0MjpQUkQtZWFhM2Q3ZTYzNTFiLWY0ZmItNDk1NC04ZWVmLWIyN2Q='                  
            ],

            'form_params' => [ 

                /// indica che cerchiamo di ottenere le credenziali per una applicazione 
                /// ( a differenza dello user_credentials che serve per le credenziali utente )
                'grant_type' => 'client_credentials',
                
                /// indica che utilizzeremo le feed api
                'scope' => 'https://api.ebay.com/oauth/api_scope/buy.item.feed' 
            ]
        ]);

        if( empty($response) ) return '';

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return '';

        /// la trasforma in array associativo
        $data = json_decode( $string_data, true );

        return $data[ 'access_token' ];
    }

    
    public static function getInstance() : ApiClient {

        if( self::$instance === null ) {

            self::$instance = new self;
        }

        return self::$instance;
    }

}