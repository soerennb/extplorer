<?php

namespace App\Services;

use RuntimeException;

/** Wall-clock budget for filesystem operations that walk untrusted trees. */
final class OperationBudget
{
    private int $deadlineNanoseconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 1) {
            throw new RuntimeException('Operation budget must be at least one second.');
        }

        $this->deadlineNanoseconds = hrtime(true) + ($seconds * 1_000_000_000);
    }

    public function tick(): void
    {
        if (hrtime(true) > $this->deadlineNanoseconds) {
            throw new RuntimeException('The operation exceeded the configured time limit.');
        }
    }
}
