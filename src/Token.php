<?php

namespace Blazemedia\EbayProductFeedApi;

use Exception;

class Token {

    public string $token;
    private int $expiresOn;
    private $file;
    private $scope;

    function __construct( $scope ) {

        $this->file = "./{$scope}.token.ini";

        $this->read();
    }

    protected function write () {

         /// prova ad aprire il file in scrittura
         $file = fopen( $this->file, 'w');

         if( $file === false ) return; 
 
         fwrite( $file, "token=\"{$this->token}\n\"" );
         fwrite( $file, "expires_on=\"{$this->expiresOn}\"" );
         fclose( $file ); 
    }

    protected function read() {

        try {
            
            /// viene recuperato il token dal contenuto del file
            $token_data = parse_ini_file( $this->file );

        } catch ( Exception $e ) {

            /// se qualcosa va storto ritorna un token vuoto e scaduto un ora fa
            $token_data = [
                'token'       => '',
                'expires_on'  => strtotime('now') - 3600
            ];
        }

        $this->token     =       trim( $token_data[ "token"      ] );
        $this->expiresOn = (int) trim( $token_data[ "expires_on" ] );

    }

    /**
     * Imposta un nuovo token
     *
     * @param string $token
     * @param string $expire_date ( in the format YYYY-MM-DD )
     * @return void
     */
    function set( string $token, int $expires_on ) {
        
        $this->token = $token;
        $this->expiresOn = $expires_on;

        $this->write();
    }


    /**
     * Return true if the token is expired, false otherwise
     *
     * @return boolean
     */
    function isExpired() {

        return strtotime('now') > $this->expiresOn;
    }

}