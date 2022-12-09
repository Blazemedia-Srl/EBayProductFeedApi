<?php

namespace Blazemedia\EbayProductFeedApi;

use Blazemedia\EbayProductFeedApi\Token;
use GuzzleHttp\Client;

trait OAuth {

     /**
     * Ritorna il token per l'autorizzazione
     *
     * @return Token
     */
    protected function getAuthToken( Client $httpClient, string $scope = '' ) : Token {

        $token = new Token( $scope );

        if( $token->isExpired() ) {

            /// scarica un nuovo token
            $token_data = $this->getTokenData( $httpClient, $scope );

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
    protected function getTokenData( Client $httpClient, string $scope ) : array { 

        $apiTokenRequestCall = 'identity/v1/oauth2/token';
    
        /// Effettua la chiamata
        $response = $httpClient->post( $apiTokenRequestCall, [

            'headers' => [

                'Content-Type' => 'application/x-www-form-urlencoded',

                /// indica che il marketplace è quello italiano
                //'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',

                /// basic auth con le credenziali in formato base64
                'Authorization' => 'Basic QmxhemVNZWQtdGVsZWZvbmktUFJELWZlYWEzZDdlNi0yNjAzY2U0MjpQUkQtZWFhM2Q3ZTYzNTFiLWY0ZmItNDk1NC04ZWVmLWIyN2Q='
            ],

            'form_params' => [ 

                /// indica che cerchiamo di ottenere le credenziali per una applicazione 
                /// ( a differenza dello user_credentials che serve per le credenziali utente )
                'grant_type' => 'client_credentials',
                
                /// indica che utilizzeremo le feed api
                'scope' => 'https://api.ebay.com/oauth/api_scope' . ( $scope == '' ? '' : "/{$scope}" )
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

}
