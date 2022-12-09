<?php 

namespace Tests;

use Blazemedia\EbayProductFeedApi\TaxonomyApiClient;
use Exception;
use PHPUnit\Framework\TestCase;


final class TaxonomyApiClientTest extends TestCase {

    protected $client;

    protected function setUp(): void {

        parent::setUP();

        $this->client = TaxonomyApiClient::getInstance();

    }



    /** @test */
    public function it_can_connect_to_eBay_and_retrieve_a_token() {

        $this->assertFalse( $this->client->token->isExpired() );
    }


    /** @test */
    public function it_can_get_default_category_id() {

        $id = 0;
        try{ 
            $id = $this->client->GetDefaultCategoryTreeID();
        
        } catch( Exception $e ){

            var_dump( $e->getMessage() );
        }
      
      
        $this->assertGreaterThan( 0, $id );
    }

    /** @test */
    public function it_can_get_the_main_category_tree() {

        $id = 0;
        try{ 
            $id = $this->client->GetDefaultCategoryTreeID();
        
        } catch( Exception $e ){

            var_dump( $e->getMessage() );
        }

        $tree= $this->client->getCategoryTree( $id );
      
      
        $this->assertIsObject( $tree );
    }



    /** @test */
    public function it_can_find_a_category_by_name() {

        $id = 0;
        try{ 
            $id = $this->client->GetDefaultCategoryTreeID();
        
        } catch( Exception $e ){

            var_dump( $e->getMessage() );
        }

        $tree = $this->client->getCategoryTree( $id );

        $categories = [         
            ...$this->client->findCategories( 'cellulari e smartphone', $tree )
        ];
      
        $this->assertIsArray( $categories );
    }



    /** @test */
    public function it_can_find_all_the_level_1_categories() {

        $id = 0;
        try { 
            $id = $this->client->GetDefaultCategoryTreeID();
        
        } catch( Exception $e ){

            var_dump( $e->getMessage() );
        }

        $tree = $this->client->getCategoryTree( $id );

        $categories = $this->client->getLevel1Categories( $tree );         
        

        print_r( $categories ); 

        $this->assertIsArray( $categories );
    }
    
   
}