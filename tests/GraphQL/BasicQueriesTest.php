<?php

namespace Tests\GraphQL;

use Tests\TestCase;

class BasicQueriesTest extends TestCase
{
    /** @test */
    public function it_responds_to_ping_query()
    {
        $query = '{ ping }';

        $response = $this->graphQL($query);

        $response->assertJsonFragment([
            'ping' => 'pong'
        ]);
    }

    /** @test */
    public function it_returns_version_information()
    {
        $query = '{
            version {
                version
                laravel
                environment
                timestamp
            }
        }';

        $response = $this->graphQL($query);

        $response->assertJsonStructure([
            'data' => [
                'version' => [
                    'version',
                    'laravel',
                    'environment',
                    'timestamp'
                ]
            ]
        ]);
    }

    /** @test */
    public function it_returns_health_check_information()
    {
        $query = '{
            health {
                service
                status
                details
            }
        }';

        $response = $this->graphQL($query);

        $response->assertJsonStructure([
            'data' => [
                'health' => [
                    '*' => [
                        'service',
                        'status',
                        'details'
                    ]
                ]
            ]
        ]);
    }

}