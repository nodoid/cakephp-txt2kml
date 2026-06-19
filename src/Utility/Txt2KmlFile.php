<?php
declare(strict_types=1);

namespace Txt2Kml\Utility;

use RuntimeException;

/**
 * File-oriented conversion helpers. These work with plain file system paths and
 * have no framework dependency, so they can be driven directly from a controller
 * or command (for example, when a file path is already known).
 *
 * PHP/CakePHP port of the .NET `Txt2KmlFile` helpers.
 */
final class Txt2KmlFile
{
    /**
     * Derives the KML output path for a given text file path by replacing the
     * extension with `.kml` (e.g. `waypoints.txt` -> `waypoints.kml`).
     */
    public static function getKmlPath(string $textFilePath): string
    {
        if (trim($textFilePath) === '') {
            throw new RuntimeException('The text file path must not be empty.');
        }

        $info = pathinfo($textFilePath);
        $dir = $info['dirname'] ?? '';
        $base = $info['filename'] ?? '';
        $prefix = ($dir === '' || $dir === '.') ? '' : $dir . DIRECTORY_SEPARATOR;

        return $prefix . $base . '.kml';
    }

    /**
     * Reads a text file, converts it to KML, and writes the result.
     *
     * @param string $textFilePath Path to the source `.txt` file.
     * @param string|null $outputPath Optional explicit output path. When omitted, the
     *   source path is used with its extension replaced by `.kml`.
     * @param string|null $documentName Optional KML document name. Defaults to the source
     *   file name (without extension).
     * @return string The path the KML file was written to.
     * @throws \RuntimeException When the source cannot be read or the output cannot be written.
     * @throws \Txt2Kml\Utility\Txt2KmlFormatException A non-comment line is malformed.
     */
    public static function convertFile(
        string $textFilePath,
        ?string $outputPath = null,
        ?string $documentName = null,
    ): string {
        if (trim($textFilePath) === '') {
            throw new RuntimeException('The text file path must not be empty.');
        }

        $text = @file_get_contents($textFilePath);
        if ($text === false) {
            throw new RuntimeException("Unable to read text file: {$textFilePath}");
        }

        $output = $outputPath ?? self::getKmlPath($textFilePath);
        $name = $documentName ?? pathinfo($textFilePath, PATHINFO_FILENAME);
        $kml = Txt2Kml::convert($text, $name);

        if (@file_put_contents($output, $kml) === false) {
            throw new RuntimeException("Unable to write KML file: {$output}");
        }

        return $output;
    }

    /**
     * Reads a text file and returns a {@see KmlConversionResult} without writing
     * anything to disk.
     *
     * @throws \RuntimeException When the source cannot be read.
     * @throws \Txt2Kml\Utility\Txt2KmlFormatException A non-comment line is malformed.
     */
    public static function convertFileToResult(string $textFilePath, ?string $documentName = null): KmlConversionResult
    {
        if (trim($textFilePath) === '') {
            throw new RuntimeException('The text file path must not be empty.');
        }

        $text = @file_get_contents($textFilePath);
        if ($text === false) {
            throw new RuntimeException("Unable to read text file: {$textFilePath}");
        }

        $name = $documentName ?? pathinfo($textFilePath, PATHINFO_FILENAME);
        $waypoints = Txt2Kml::parse($text);
        $kml = Txt2Kml::toKml($waypoints, $name);

        return new KmlConversionResult(
            $kml,
            basename(self::getKmlPath($textFilePath)),
            count($waypoints),
            basename($textFilePath),
        );
    }
}
