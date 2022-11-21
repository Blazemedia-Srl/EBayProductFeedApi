<?php 

namespace Tests;

use Blazemedia\EbayProductFeedApi\ApiClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase {

    protected $client;

    protected function setUp(): void {

        parent::setUP();

        $this->client = ApiClient::getInstance();

    }

    /** @test */
    public function it_can_do_tests() {
    
        $this->assertTrue( true );
    }

    /** @test */
    public function it_can_connect_to_eBay_and_retrieve_a_token() {

        $this->assertEquals( strlen($this->client->token) , 1928 );
    }


    /** @test */
    public function it_can_get_files() {

        $files = $this->client->GetFiles();
      
        $this->assertNotEquals( count( $files ), 0 );
    }

    /** @test */
    public function it_can_download_file() {

        $files = $this->client->GetFiles();

        $bytes = $this->client->download( $files[0]);
      
        $this->assertGreaterThan( 0,  $bytes );
    }
}