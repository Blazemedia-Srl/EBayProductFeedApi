<?php

namespace Blazemedia\EbayProductFeedApi;

use Exception;
use GuzzleHttp\Client;
use Blazemedia\EbayProductFeedApi\Token;

class TaxonomyApiClient {

    use OAuth;

    private static ?TaxonomyApiClient $instance = null; // $istance is null or TaxonomyApiClient

    private Client $httpClient;
        
    public Token $token;
    
    private $api = 'https://api.ebay.com/';

    private function __construct( ) {

        /// istanzia il client 
        /// e imposta i valori di base delle chiamate 
        $this->httpClient = new Client([
            
            'base_uri' => $this->api,
            'verify'   => false, // Disable validation entirely (don't do this!).            
        ]);

        /// effettua il login all'api e rende disponibile
        /// il token all'istanza
        $this->token = $this->getAuthToken( $this->httpClient );
    }


    /**
     * Restituisce l'array dei file da scaricare
     *
     * @param integer $lookBackDays - How many days ago to start gathering offers, defaults to 1
     * @return int
     */
    function GetDefaultCategoryTreeID(  ) : int { 

        $apiTokenRequestCall = '/commerce/taxonomy/v1/get_default_category_tree_id';
        
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
                'marketplace_id' => 'EBAY_IT',      
            ]
        ]);
        

        if( empty($response) ) return [];

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return [];

        /// la trasforma in array associativo
        return json_decode( $string_data )->categoryTreeId;

    } 



    /**
     * Restituisce l'array delle subcategories di un certo tree
     *
     * @param integer $lookBackDays - How many days ago to start gathering offers, defaults to 1
     * @return object
     */
    function getCategoryTree( int $categoryId ) : object { 

        $apiTokenRequestCall = "/commerce/taxonomy/v1/category_tree/{$categoryId}";
        
        /// Effettua la chiamata
        $response = $this->httpClient->get( $apiTokenRequestCall, [

            'headers' => [

                /// indica che il marketplace è quello italiano
                'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_IT',
                /// Accede con il token oauth 2 
                'Authorization' => "Bearer {$this->token->token}"                  
            ]
        ]);
        

        if( empty($response) ) return [];

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return [];

        /// la trasforma 
        return json_decode( $string_data )->rootCategoryNode;
    } 

    
    function getCategorySubtree( int $categoryId, int $subcategoryId ) {

        $apiTokenRequestCall = "/commerce/taxonomy/v1/category_tree/{$categoryId}/get_category_subtree";
        
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
                'category_id' => $subcategoryId
            ]
        ]);
        

        if( empty($response) ) return [];

        /// prende lo stream dati come stringa
        $string_data = $response->getBody()->getContents();

        if( empty( $string_data ) ) return [];

        /// la trasforma 
        return json_decode( $string_data )->rootCategoryNode;

    }


    /**
     * Restituisce le categorie di livello 1
     *     
     * @return array
     */
    function getLevel1Categories( object $tree ) : array { 

        $data = [];

        if( isset( $tree->categoryTreeNodeLevel) && $tree->categoryTreeNodeLevel == 1 ) {

            $data = [
                [
                    'categoryId'   => $tree->category->categoryId,
                    'categoryName' => $tree->category->categoryName,
                    'level'        => $tree->categoryTreeNodeLevel
                ]
            ];
        } 

        if( isset( $tree->childCategoryTreeNodes ) && !empty( $tree->childCategoryTreeNodes ) ) {
            
            $subtree = array_map( fn( $item ) => $this->getLevel1Categories( $item ), $tree->childCategoryTreeNodes ); 

            $data = array_merge( $data, ...array_filter( $subtree, fn( $item ) => !empty($item) ) );

        }

        return $data;
    } 



    /**
     * Cerca le categorie che hanno un nome simile a quello inserito
     *
     * @param string $name - Category name to search
     * @return array
     */
    function findCategories( string $name, object $tree ) : array { 

        $data = [];

        if( str_contains( strtolower( $tree->category->categoryName ), strtolower( $name ) ) ) {

            $data = [
                [
                    'categoryId'   => $tree->category->categoryId,
                    'categoryName' => $tree->category->categoryName,
                    'level'        => $tree->categoryTreeNodeLevel
                ]
            ];

        } 

        if( isset($tree->childCategoryTreeNodes) && !empty( $tree->childCategoryTreeNodes ) ) {
            
            $subtree = array_map( fn( $item ) => $this->findCategories( $name, $item ), $tree->childCategoryTreeNodes ); 

            $data = array_merge( $data, ...array_filter( $subtree, fn( $item ) => !empty($item) ) );

        }

        return $data;
    } 

  
    public static function getInstance() : TaxonomyApiClient {

        if( self::$instance === null ) {

            self::$instance = new self;
        }

        return self::$instance;
    }

}