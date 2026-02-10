<?php

namespace App\Services\PartHandlers;

interface PartHandlerInterface
{
    public function formatMetadata(array $data): array;

    public function getValidationRules(): array;
}
