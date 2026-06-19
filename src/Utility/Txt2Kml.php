<?php
declare(strict_types=1);

namespace Txt2Kml\Utility;

use DOMDocument;
use DOMElement;

/**
 * Converts simple delimited text into KML (Keyhole Markup Language) documents.
 *
 * Input format: one waypoint per line, comma-separated:
 *
 *     latitude,longitude[,name][,description]
 *
 * Blank lines and lines beginning with '#' are ignored. Whitespace around
 * fields is trimmed. Numbers are parsed using the invariant (C) locale so a
 * '.' is always the decimal separator regardless of server locale.
 *
 * PHP/CakePHP port of the .NET `Plugin.Txt2KML` library.
 */
final class Txt2Kml
{
    public const KML_NAMESPACE = 'http://www.opengis.net/kml/2.2';

    /**
     * Parses delimited text into a list of {@see Waypoint}s.
     *
     * @param string|null $text The delimited source text.
     * @return array<int, \Txt2Kml\Utility\Waypoint>
     * @throws \Txt2Kml\Utility\Txt2KmlFormatException A non-comment line is malformed.
     */
    public static function parse(?string $text): array
    {
        $waypoints = [];
        if ($text === null || trim($text) === '') {
            return $waypoints;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $normalized);

        foreach ($lines as $index => $line) {
            $raw = trim($line);
            if ($raw === '' || $raw[0] === '#') {
                continue;
            }

            $lineNumber = $index + 1;
            $fields = explode(',', $raw);
            if (count($fields) < 2) {
                throw new Txt2KmlFormatException(
                    $lineNumber,
                    "Line {$lineNumber}: expected at least 'latitude,longitude' but got '{$raw}'.",
                );
            }

            $lat = self::parseCoordinate($fields[0]);
            if ($lat === null || $lat < -90.0 || $lat > 90.0) {
                $value = trim($fields[0]);
                throw new Txt2KmlFormatException(
                    $lineNumber,
                    "Line {$lineNumber}: invalid latitude '{$value}'.",
                );
            }

            $lon = self::parseCoordinate($fields[1]);
            if ($lon === null || $lon < -180.0 || $lon > 180.0) {
                $value = trim($fields[1]);
                throw new Txt2KmlFormatException(
                    $lineNumber,
                    "Line {$lineNumber}: invalid longitude '{$value}'.",
                );
            }

            $name = count($fields) > 2 ? self::nullify($fields[2]) : null;
            $description = count($fields) > 3
                ? self::nullify(implode(',', array_slice($fields, 3)))
                : null;

            $waypoints[] = new Waypoint($lat, $lon, $name, $description);
        }

        return $waypoints;
    }

    /**
     * Attempts to parse delimited text without throwing.
     *
     * @param string|null $text The delimited source text.
     * @param \Txt2Kml\Utility\Txt2KmlFormatException|null $error Set to the failure when parsing fails.
     * @return array<int, \Txt2Kml\Utility\Waypoint>|null The waypoints, or null on failure.
     */
    public static function tryParse(?string $text, ?Txt2KmlFormatException &$error = null): ?array
    {
        try {
            $error = null;

            return self::parse($text);
        } catch (Txt2KmlFormatException $ex) {
            $error = $ex;

            return null;
        }
    }

    /**
     * Parses delimited text and returns a complete KML document as a string.
     *
     * @param string|null $text The delimited source text.
     * @param string|null $documentName Optional KML document name.
     * @return string The KML document.
     * @throws \Txt2Kml\Utility\Txt2KmlFormatException A non-comment line is malformed.
     */
    public static function convert(?string $text, ?string $documentName = null): string
    {
        return self::toKml(self::parse($text), $documentName);
    }

    /**
     * Attempts to convert delimited text to KML without throwing.
     *
     * @param string|null $text The delimited source text.
     * @param \Txt2Kml\Utility\Txt2KmlFormatException|null $error Set to the failure when conversion fails.
     * @param string|null $documentName Optional KML document name.
     * @return string|null The KML document, or null on failure.
     */
    public static function tryConvert(
        ?string $text,
        ?Txt2KmlFormatException &$error = null,
        ?string $documentName = null,
    ): ?string {
        $waypoints = self::tryParse($text, $error);
        if ($waypoints === null) {
            return null;
        }

        return self::toKml($waypoints, $documentName);
    }

    /**
     * Builds a complete KML document from a set of waypoints.
     *
     * @param iterable<\Txt2Kml\Utility\Waypoint> $waypoints The waypoints to render.
     * @param string|null $documentName Optional KML document name.
     * @return string The KML document.
     */
    public static function toKml(iterable $waypoints, ?string $documentName = null): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        // Every element is created in the KML default namespace so the
        // serialized document carries a single `xmlns` on the root, matching
        // the .NET output and keeping Google Earth happy.
        $kml = self::element($dom, 'kml');
        $dom->appendChild($kml);

        $document = self::element($dom, 'Document');
        $kml->appendChild($document);

        if ($documentName !== null && trim($documentName) !== '') {
            $document->appendChild(self::element($dom, 'name', $documentName));
        }

        foreach ($waypoints as $waypoint) {
            $document->appendChild(self::buildPlacemark($dom, $waypoint));
        }

        return (string)$dom->saveXML();
    }

    private static function buildPlacemark(DOMDocument $dom, Waypoint $waypoint): DOMElement
    {
        $placemark = self::element($dom, 'Placemark');

        if ($waypoint->name !== null && trim($waypoint->name) !== '') {
            $placemark->appendChild(self::element($dom, 'name', $waypoint->name));
        }

        if ($waypoint->description !== null && trim($waypoint->description) !== '') {
            $placemark->appendChild(self::element($dom, 'description', $waypoint->description));
        }

        $point = self::element($dom, 'Point');
        $point->appendChild(self::element($dom, 'coordinates', self::formatCoordinates($waypoint)));
        $placemark->appendChild($point);

        return $placemark;
    }

    /**
     * Creates an element in the KML namespace, optionally with a text value.
     * Using a text node (rather than the value constructor argument) ensures
     * special characters such as '&' and '<' are escaped.
     */
    private static function element(DOMDocument $dom, string $name, ?string $value = null): DOMElement
    {
        $element = $dom->createElementNS(self::KML_NAMESPACE, $name);
        if ($value !== null) {
            $element->appendChild($dom->createTextNode($value));
        }

        return $element;
    }

    /**
     * KML coordinate order is longitude,latitude[,altitude].
     */
    private static function formatCoordinates(Waypoint $waypoint): string
    {
        $lon = self::formatNumber($waypoint->longitude);
        $lat = self::formatNumber($waypoint->latitude);
        $alt = self::formatNumber($waypoint->altitude ?? 0.0);

        return "{$lon},{$lat},{$alt}";
    }

    /**
     * Formats a float using the invariant locale ('.' decimal separator, no
     * thousands separator, no trailing zeros).
     */
    private static function formatNumber(float $value): string
    {
        // Avoid locale-dependent output and scientific notation; trim trailing zeros.
        $formatted = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');

        return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
    }

    /**
     * Parses a coordinate field using the invariant locale, returning null when
     * the value is not a valid number.
     */
    private static function parseCoordinate(string $value): ?float
    {
        $trimmed = trim($value);
        if ($trimmed === '' || !is_numeric($trimmed)) {
            return null;
        }

        return (float)$trimmed;
    }

    private static function nullify(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
