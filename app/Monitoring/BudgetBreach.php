<?php

namespace App\Monitoring;

final readonly class BudgetBreach
{
    public function __construct(
        public string $domain,
        public float $requestedRate,
        public int $limit,
        public bool $perUser,
    ) {}

    public function message(): string
    {
        return __($this->perUser
            ? 'validation.target_budget_user'
            : 'validation.target_budget_instance', [
                'domain' => $this->domain,
                'limit' => $this->limit,
                'rate' => round($this->requestedRate, 2),
            ]);
    }
}
