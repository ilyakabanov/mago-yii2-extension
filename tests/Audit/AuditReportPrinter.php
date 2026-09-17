<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class AuditReportPrinter
{
    /**
     * @param array{sniffs: int, concrete-diagnostics: int, fixture-source: string} $scope
     * @param array{lint: int, formatter: int, different: int, unsupported: int, unclassified: int} $summary
     */
    public function display(array $scope, array $summary): void
    {
        printf(
            "Differential audit passed: %d sniffs, %d concrete diagnostics.\n"
            . "lint=%d formatter=%d different=%d unsupported=%d unclassified=%d\n",
            $scope['sniffs'],
            $scope['concrete-diagnostics'],
            $summary['lint'],
            $summary['formatter'],
            $summary['different'],
            $summary['unsupported'],
            $summary['unclassified'],
        );
    }
}
