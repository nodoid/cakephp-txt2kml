<?php
declare(strict_types=1);

namespace Txt2Kml\Test\TestCase\Utility;

use PHPUnit\Framework\TestCase;
use Txt2Kml\Utility\Txt2Kml;
use Txt2Kml\Utility\Txt2KmlFormatException;
use Txt2Kml\Utility\Waypoint;

/**
 * Mirrors the .NET Txt2KmlTests, ported to PHPUnit.
 */
class Txt2KmlTest extends TestCase
{
    public function testParseNullOrEmptyReturnsEmpty(): void
    {
        $this->assertSame([], Txt2Kml::parse(null));
        $this->assertSame([], Txt2Kml::parse(''));
        $this->assertSame([], Txt2Kml::parse("   \n  \n"));
    }

    public function testParseSkipsBlankAndCommentLines(): void
    {
        $text = "# a header comment\n\n-12.46,130.84\n   # indented comment\n-37.81,144.96\n";

        $this->assertCount(2, Txt2Kml::parse($text));
    }

    public function testParseLatLonOnlyPopulatesCoordinates(): void
    {
        $waypoints = Txt2Kml::parse('-12.4634,130.8456');
        $this->assertCount(1, $waypoints);

        $wp = $waypoints[0];
        $this->assertSame(-12.4634, $wp->latitude);
        $this->assertSame(130.8456, $wp->longitude);
        $this->assertNull($wp->name);
        $this->assertNull($wp->description);
    }

    public function testParseNameAndDescriptionAreCaptured(): void
    {
        $waypoints = Txt2Kml::parse('-33.8688,151.2093,Sydney,Harbour city, with commas');
        $wp = $waypoints[0];

        $this->assertSame('Sydney', $wp->name);
        // Everything after the third comma is treated as the description.
        $this->assertSame('Harbour city, with commas', $wp->description);
    }

    public function testParseTrimsWhitespaceAroundFields(): void
    {
        $waypoints = Txt2Kml::parse('  -12.46 , 130.84 ,  Darwin  ');
        $wp = $waypoints[0];

        $this->assertSame(-12.46, $wp->latitude);
        $this->assertSame('Darwin', $wp->name);
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function invalidLineProvider(): array
    {
        return [
            ['not-a-number,130.0'],
            ['91.0,130.0'],   // latitude out of range
            ['-12.0,200.0'],  // longitude out of range
            ['-12.0'],        // missing longitude
        ];
    }

    /**
     * @dataProvider invalidLineProvider
     */
    public function testParseInvalidLineThrows(string $text): void
    {
        $this->expectException(Txt2KmlFormatException::class);
        Txt2Kml::parse($text);
    }

    public function testParseInvalidLineReportsLineNumber(): void
    {
        try {
            Txt2Kml::parse("-12.0,130.0\nbad-line");
            $this->fail('Expected Txt2KmlFormatException');
        } catch (Txt2KmlFormatException $ex) {
            $this->assertSame(2, $ex->lineNumber);
        }
    }

    public function testTryParseReturnsNullAndErrorOnFailure(): void
    {
        $error = null;
        $result = Txt2Kml::tryParse('nope', $error);

        $this->assertNull($result);
        $this->assertInstanceOf(Txt2KmlFormatException::class, $error);
    }

    public function testTryConvertReturnsKmlOnSuccess(): void
    {
        $error = null;
        $kml = Txt2Kml::tryConvert('-12.46,130.84', $error);

        $this->assertNull($error);
        $this->assertIsString($kml);
        $this->assertStringContainsString('<Placemark>', $kml);
    }

    public function testToKmlProducesWellFormedDocumentWithCorrectNamespace(): void
    {
        $kml = Txt2Kml::convert('-12.4634,130.8456,Darwin', 'My Doc');

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($kml));
        $this->assertSame('kml', $dom->documentElement->localName);
        $this->assertSame(Txt2Kml::KML_NAMESPACE, $dom->documentElement->namespaceURI);
        $this->assertStringContainsString('<name>My Doc</name>', $kml);
    }

    public function testToKmlUsesLongitudeLatitudeAltitudeOrder(): void
    {
        $kml = Txt2Kml::toKml([new Waypoint(-12.4634, 130.8456, 'Darwin', null, 25.0)]);

        // KML coordinate order is longitude,latitude,altitude.
        $this->assertStringContainsString('<coordinates>130.8456,-12.4634,25</coordinates>', $kml);
    }

    public function testToKmlEscapesSpecialCharacters(): void
    {
        $kml = Txt2Kml::toKml([new Waypoint(0.0, 0.0, 'A & B <tag>')]);

        $this->assertStringContainsString('A &amp; B &lt;tag&gt;', $kml);
        // Re-parsing must succeed, proving the output is well-formed.
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($kml));
    }
}
