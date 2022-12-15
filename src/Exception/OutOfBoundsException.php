<?php

declare(strict_types=1);

namespace GuidoFaecke\MezzioDoctrineOdm\Exception;

use function sprintf;

class OutOfBoundsException extends \OutOfBoundsException implements ExceptionInterface
{
    public static function forMissingConfigKey(string $key): self
    {
        return new self(sprintf('Missing "%s" config key', $key));
    }
}
