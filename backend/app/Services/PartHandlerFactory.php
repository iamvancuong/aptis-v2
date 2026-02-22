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
            'reading-3' => app(\App\Services\PartHandlers\Reading\ReadingPart3Handler::class),
            'reading-4' => app(\App\Services\PartHandlers\Reading\ReadingPart4Handler::class),
            'listening-1' => app(\App\Services\PartHandlers\Listening\ListeningPart1Handler::class),
            'listening-2' => app(\App\Services\PartHandlers\Listening\ListeningPart2Handler::class),
            'listening-3' => app(\App\Services\PartHandlers\Listening\ListeningPart3Handler::class),
            'listening-4' => app(\App\Services\PartHandlers\Listening\ListeningPart4Handler::class),
            'writing-1' => app(\App\Services\PartHandlers\Writing\WritingPart1Handler::class),
            'writing-2' => app(\App\Services\PartHandlers\Writing\WritingPart2Handler::class),
            'writing-3' => app(\App\Services\PartHandlers\Writing\WritingPart3Handler::class),
            'writing-4' => app(\App\Services\PartHandlers\Writing\WritingPart4Handler::class),
            default => throw new Exception("Handler not found for skill: {$skill}, part: {$part}"),
        };
    }
}
