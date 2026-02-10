<?php

namespace App\Services;

use App\Services\PartHandlers\PartHandlerInterface;
use App\Services\PartHandlers\Reading\ReadingPart1Handler;
use Exception;

class PartHandlerFactory
{
    public function getHandler(string $skill, int $part): PartHandlerInterface
    {
        $key = strtolower($skill).'-'.$part;

        return match ($key) {
            'reading-1' => app(ReadingPart1Handler::class),
            'reading-2' => app(\App\Services\PartHandlers\Reading\ReadingPart2Handler::class),
            default => throw new Exception("Handler not found for skill: {$skill}, part: {$part}"),
        };
    }
}
