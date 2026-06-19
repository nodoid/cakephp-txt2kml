<?php
declare(strict_types=1);

namespace Txt2Kml\Test\TestCase\Utility;

use PHPUnit\Framework\TestCase;
use Txt2Kml\Utility\Txt2KmlFile;

class Txt2KmlFileTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'txt2kml_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: []);
        @rmdir($this->dir);
    }

    public function testGetKmlPathReplacesExtension(): void
    {
        $this->assertSame(
            '/tmp/waypoints.kml',
            Txt2KmlFile::getKmlPath('/tmp/waypoints.txt'),
        );
        $this->assertSame('waypoints.kml', Txt2KmlFile::getKmlPath('waypoints.txt'));
    }

    public function testConvertFileWritesKmlNextToSource(): void
    {
        $source = $this->dir . DIRECTORY_SEPARATOR . 'points.txt';
        file_put_contents($source, "-12.46,130.84,Darwin\n-37.81,144.96,Melbourne\n");

        $output = Txt2KmlFile::convertFile($source);

        $this->assertSame($this->dir . DIRECTORY_SEPARATOR . 'points.kml', $output);
        $this->assertFileExists($output);
        $this->assertStringContainsString('<name>Darwin</name>', (string)file_get_contents($output));
    }

    public function testConvertFileToResultCountsWaypoints(): void
    {
        $source = $this->dir . DIRECTORY_SEPARATOR . 'points.txt';
        file_put_contents($source, "-12.46,130.84\n-37.81,144.96\n");

        $result = Txt2KmlFile::convertFileToResult($source);

        $this->assertSame(2, $result->waypointCount);
        $this->assertSame('points.kml', $result->suggestedFileName);
        $this->assertSame('points.txt', $result->sourceFileName);
    }
}
