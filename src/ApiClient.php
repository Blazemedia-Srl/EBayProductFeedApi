<?php

namespace Blazemedia\EbayProductFeedApi;

use Exception;
use GuzzleHttp\Client;
use Blazemedia\EbayProductFeedApi\Token;

class ApiClient {

    private static ?ApiClient $instance = null;

    private Client $httpClient;
        
    public Token $token;
    
    private $api = 'https://api.ebay.com/';

    const DEF_CATERGORY_ID = 11450;

    private function __construct() {

        /// istanzia il client 
        /// e imposta i valori di base delle chiamate 
        $this->httpClient = new Client([
            
            'base_uri' => $this->api,
            'verify'   => false, // Disable validation entirely (don't do this!).            
        ]);

        /// effettua il login all'api e rende disponibile
        /// il token all'istanza
        $this->token = $this->getAuthToken();
    }



    /**
     * 
     *
     * @return array
     */

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
                'category_ids' => ApiClient::DEF_CATERGORY_ID,
                
                [
                    "key" => "category_ids",
                    "value" =>  "58058,3187",
                    "description" =>  "L1 categories; csv",
                    "disabled" =>  true               
                ],
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


    /**
     * Ritorna il token per l'autorizzazione
     *
     * @return Token
     */
    protected function getAuthToken() : Token {

        $token = new Token;

        if( $token->isExpired() ) {

            /// scarica un nuovo token
            $token_data = $this->getTokenData();

            /// il timestamp attuale + la durata del token - un minuto ( non si sa mai )
            $expires_on = strtotime('now') + $token_data['expires_in'] - 60;

            /// salva il nuovo token
            $token->set( $token_data[ 'access_token' ],  $expires_on );
        }

        return $token;    
    }


    /**
     * Effettua la chiamata per la generazione del token
     * da utilizzare nelle chiamate
     *
     * @return array
     */
    protected function getTokenData() : array { 

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

        return $data;
    }

    
    public static function getInstance() : ApiClient {

        if( self::$instance === null ) {

            self::$instance = new self;
        }

        return self::$instance;
    }

}