# Txt2Kml (CakePHP plugin)

A CakePHP 5 plugin that converts simple comma-delimited waypoint text into
[KML](https://developers.google.com/kml) documents. It is a PHP port of the
.NET `Plugin.Txt2KML` library and keeps the same input format, validation rules
and output shape.

## Input format

One waypoint per line, comma-separated:

```
latitude,longitude[,name][,description]
```

* Blank lines and lines beginning with `#` are ignored.
* Whitespace around fields is trimmed.
* Numbers use `.` as the decimal separator regardless of server locale.
* Latitude must be within `-90..90`, longitude within `-180..180`.
* Everything after the third comma is treated as the description (so the
  description may itself contain commas).

Example:

```
# Australian capitals
-35.2809,149.1300,Canberra,The capital
-33.8688,151.2093,Sydney
-37.8136,144.9631,Melbourne
```

## Installation

```bash
composer require olgadowns/cakephp-txt2kml
```

Load the plugin in `src/Application.php`:

```php
$this->addPlugin('Txt2Kml');
```

Loading the plugin is only needed for the bundled HTTP controller/route. The
converter classes under `Txt2Kml\Utility` have **no** framework dependency and
can be used on their own.

## Usage

### Convert a string

```php
use Txt2Kml\Utility\Txt2Kml;

$kml = Txt2Kml::convert("-12.4634,130.8456,Darwin", documentName: 'My Doc');
```

### Parse into value objects

```php
$waypoints = Txt2Kml::parse($text); // array<Txt2Kml\Utility\Waypoint>
foreach ($waypoints as $wp) {
    echo $wp->latitude, ' ', $wp->longitude, ' ', $wp->name, PHP_EOL;
}
```

### Convert without throwing

PHP has no `out` parameters, so the `try*` helpers use a by-reference error
argument and return `null` on failure:

```php
$error = null;
$kml = Txt2Kml::tryConvert($text, $error);
if ($kml === null) {
    // $error is a Txt2KmlFormatException
    echo "Line {$error->lineNumber}: {$error->getMessage()}";
}
```

### Work with files

```php
use Txt2Kml\Utility\Txt2KmlFile;

// Writes waypoints.kml next to waypoints.txt and returns the output path.
$path = Txt2KmlFile::convertFile('/data/waypoints.txt');

// Or get the KML + metadata without touching disk.
$result = Txt2KmlFile::convertFileToResult('/data/waypoints.txt');
echo $result->waypointCount, ' -> ', $result->suggestedFileName;
```

### Over HTTP

With the plugin loaded, `POST /txt2kml/convert` accepts a `text` field (and
optional `documentName` / `fileName`) and streams back a downloadable KML file.
Malformed input returns `422` with a JSON body `{ "error": ..., "line": ... }`.

## API surface

| .NET member                | PHP equivalent                                  |
| -------------------------- | ----------------------------------------------- |
| `Txt2Kml.Parse`            | `Txt2Kml::parse(?string): Waypoint[]`           |
| `Txt2Kml.TryParse`         | `Txt2Kml::tryParse(?string, &$error): ?array`   |
| `Txt2Kml.Convert`          | `Txt2Kml::convert(?string, ?string): string`    |
| `Txt2Kml.TryConvert`       | `Txt2Kml::tryConvert(?string, &$error, ?string)`|
| `Txt2Kml.ToKml`            | `Txt2Kml::toKml(iterable, ?string): string`     |
| `Txt2KmlFile.ConvertFile`  | `Txt2KmlFile::convertFile(...)`                 |
| `Waypoint` record          | `Txt2Kml\Utility\Waypoint`                      |
| `Txt2KmlFormatException`   | `Txt2Kml\Utility\Txt2KmlFormatException`        |

The `*Async` methods from the .NET library are intentionally omitted — they are
not meaningful in the synchronous PHP request model.

## Tests

```bash
composer install
composer test
```

## License

MIT
