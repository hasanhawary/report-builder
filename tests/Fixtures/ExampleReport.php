<?php

namespace HasanHawary\ReportBuilder\Tests\Fixtures;

use HasanHawary\ReportBuilder\BaseReport;

class ExampleReport extends BaseReport
{
    public string $table = 'examples';

    public function getCards(): array
    {
        return $this->cardResponse([
            'total' => 5,
        ]);
    }

    public function getChart(): array
    {
        return $this->chartResponse('status', [
            ['status' => 'active', 'users_count' => 5],
        ]);
    }

    public function getBrokenChart(): array
    {
        throw new \RuntimeException('Broken chart');
    }
}
