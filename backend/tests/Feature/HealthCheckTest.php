<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test health check endpoint returns 200 and healthy JSON when DB and cache are operational.
     */
    public function test_health_check_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => [
                        'status' => 'healthy',
                    ],
                    'cache' => [
                        'status' => 'healthy',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database' => ['status', 'message'],
                    'cache' => ['status', 'message'],
                ],
            ]);
    }

    /**
     * Test health check endpoint returns 503 and reports unhealthy service when database is disconnected.
     */
    public function test_health_check_returns_503_when_database_fails(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \PDOException('SQLSTATE[HY000] [2002] Connection refused'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'database' => [
                        'status' => 'unhealthy',
                    ],
                ],
            ]);
    }
}

