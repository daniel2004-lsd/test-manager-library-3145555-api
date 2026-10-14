<?php

namespace App\Domain\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @var array<int, array{field: string, issue: string}> */
    public readonly array $details;

    public function __construct(string $field, string $issue)
    {
        $this->details = [['field' => $field, 'issue' => $issue]];
        parent::__construct($issue);
    }
}
