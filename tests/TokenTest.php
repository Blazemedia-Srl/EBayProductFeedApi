<?php 

namespace Tests;

use Blazemedia\EbayProductFeedApi\Token;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase {

    protected function setUp(): void {

        parent::setUP();

    }

    /** @test */
    public function it_can_do_tests() {
    
        $this->assertTrue( true );
    }

    /** @test */
    public function it_returns_an_expired_token_when_file_does_not_exists() {
        
        $filename = './test-file.ini';
        
        if( file_exists( $filename ) ) {

            unlink( $filename );
        }
        

        $token = new Token( $filename );

        $this->assertTrue( $token->isExpired() );
    }


    
}