<?php

namespace App\Message;

class FillPlantMessage
{
    public function __construct(
        public readonly int $plantId,
        public readonly int $gbifKey,
        public readonly string $canonicalName,
    ) {}
}
