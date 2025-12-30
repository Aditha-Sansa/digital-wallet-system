<?php

namespace App\DTOs;

use Illuminate\Support\Str;
use InvalidArgumentException;

class BulkCreditRowDTO
{
    public function __construct(
        public string $uuid,
        public float $amount
    ) {}

    public static function fromArray(array $row): self
    {
        if (! Str::isUuid($row['uuid'] ?? '')) {
            throw new InvalidArgumentException('Invalid uuid');
        }

        if (! is_numeric($row['amount']) || $row['amount'] <= 0) {
            throw new InvalidArgumentException('Invalid amount');
        }

        return new self(
            $row['uuid'],
            (float) $row['amount']
        );
    }
}
