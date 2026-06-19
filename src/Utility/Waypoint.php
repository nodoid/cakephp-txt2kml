<?php
declare(strict_types=1);

namespace Txt2Kml\Utility;

/**
 * A single geographic point to be written to KML.
 *
 * Immutable value object — the CakePHP/PHP analogue of the .NET
 * `Waypoint` record.
 */
final class Waypoint
{
    /**
     * @param float $latitude Latitude in decimal degrees (-90..90).
     * @param float $longitude Longitude in decimal degrees (-180..180).
     * @param string|null $name Optional placemark name.
     * @param string|null $description Optional placemark description.
     * @param float|null $altitude Optional altitude in metres.
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?float $altitude = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'name' => $this->name,
            'description' => $this->description,
            'altitude' => $this->altitude,
        ];
    }
}
