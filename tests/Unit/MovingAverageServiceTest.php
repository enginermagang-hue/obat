<?php

namespace Tests\Unit;

use App\Services\MovingAverageService;
use Tests\TestCase;

class MovingAverageServiceTest extends TestCase
{
    private MovingAverageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MovingAverageService::class);
    }

    public function test_predict_returns_average_of_last_3_months(): void
    {
        $data = [100, 200, 300];

        $result = $this->service->predict($data, 3);

        $this->assertSame(200, $result['jumlah']); // (100+200+300)/3
        $this->assertGreaterThanOrEqual(0, $result['confidence_lower']);
        $this->assertGreaterThanOrEqual(0, $result['confidence_upper']);
    }

    public function test_predict_returns_zero_for_empty_data(): void
    {
        $result = $this->service->predict([], 3);

        $this->assertSame(0, $result['jumlah']);
        $this->assertSame(0, $result['confidence_lower']);
        $this->assertSame(0, $result['confidence_upper']);
    }

    public function test_predict_uses_specified_window(): void
    {
        $data = [10, 20, 30, 40, 50];

        $result = $this->service->predict($data, 2);

        $this->assertSame(45, $result['jumlah']); // (40+50)/2
    }

    public function test_predict_handles_single_value(): void
    {
        $result = $this->service->predict([150], 3);

        $this->assertSame(150, $result['jumlah']);
    }

    public function test_has_sufficient_data_returns_true(): void
    {
        $this->assertTrue($this->service->hasSufficientData([1, 2, 3, 4], 3));
    }

    public function test_has_sufficient_data_returns_false(): void
    {
        $this->assertFalse($this->service->hasSufficientData([1, 2], 3));
    }

    public function test_predict_never_returns_negative_jumlah(): void
    {
        $data = [5, 3, 1];

        $result = $this->service->predict($data, 3);

        $this->assertGreaterThanOrEqual(0, $result['jumlah']);
    }
}
