<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\SecurityLogRepository;
use App\Service\SecurityLogService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SecurityLogServiceTest extends TestCase
{
    private SecurityLogRepository&MockObject $repository;
    private SecurityLogService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SecurityLogRepository::class);
        $this->service    = new SecurityLogService($this->repository);
    }

    public function testGetPaginatedListReturnsMappedItems(): void
    {
        $this->repository->method('countAll')->willReturn(4);
        $this->repository->method('findPaginated')->with(50, 0)->willReturn([
            ['id' => '9', 'ip' => '10.10.0.1', 'path' => '/translation/en', 'method' => 'GET', 'instance_id' => 'inst-3', 'is_sensitive' => '0', 'user_agent' => 'bot', 'created_at' => '2026-01-03 00:00:00'],
        ]);

        $result = $this->service->getPaginatedList(1, 50, 'translation');

        $this->assertSame('translation', $result['service']);
        $this->assertSame(4, $result['total']);
        $this->assertCount(1, $result['items']);
        $this->assertSame(9, $result['items'][0]['id']);
    }

    public function testGetPaginatedListClampsPageAndPerPage(): void
    {
        $this->repository->method('countAll')->willReturn(0);
        $this->repository->expects($this->once())->method('findPaginated')->with(100, 0)->willReturn([]);

        $this->service->getPaginatedList(0, 200, 'translation');
    }

    public function testClearDelegatesAndReturnsCount(): void
    {
        $this->repository->expects($this->once())->method('clearAll')->willReturn(12);

        $this->assertSame(12, $this->service->clear());
    }
}
