<?php
declare(strict_types=1);

namespace Txt2Kml\Utility;

/**
 * The outcome of converting a text source into KML.
 */
final class KmlConversionResult
{
    /**
     * @param string $kml The generated KML document.
     * @param string $suggestedFileName A suggested output file name (source name with a .kml extension).
     * @param int $waypointCount The number of waypoints found in the source.
     * @param string|null $sourceFileName The original source file name, when known.
     */
    public function __construct(
        public readonly string $kml,
        public readonly string $suggestedFileName,
        public readonly int $waypointCount,
        public readonly ?string $sourceFileName = null,
    ) {
    }
}
