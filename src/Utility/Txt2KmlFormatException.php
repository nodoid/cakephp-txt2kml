<?php
declare(strict_types=1);

namespace Txt2Kml\Utility;

use RuntimeException;
use Throwable;

/**
 * Thrown when a line of input text cannot be parsed into a {@see Waypoint}.
 *
 * Mirrors the .NET `Txt2KmlFormatException`, exposing the 1-based line number
 * that failed to parse.
 */
final class Txt2KmlFormatException extends RuntimeException
{
    /**
     * @param int $lineNumber The 1-based line number that failed to parse, or 0 if not line-specific.
     * @param string $message The error message.
     * @param \Throwable|null $previous The inner exception, if any.
     */
    public function __construct(
        public readonly int $lineNumber,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
