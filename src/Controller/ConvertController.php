<?php
declare(strict_types=1);

namespace Txt2Kml\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Txt2Kml\Utility\Txt2Kml;
use Txt2Kml\Utility\Txt2KmlFormatException;

/**
 * Example controller exposing the converter over HTTP.
 *
 * POST /txt2kml/convert with a `text` field (form or JSON) returns a KML
 * document as a downloadable `application/vnd.google-earth.kml+xml` response.
 * Malformed input returns a 422 with the offending line number.
 */
class ConvertController extends Controller
{
    /**
     * Converts posted delimited text into a downloadable KML document.
     */
    public function convert(): Response
    {
        $this->request->allowMethod(['post']);

        $text = (string)$this->request->getData('text', '');
        $documentName = $this->request->getData('documentName');
        $fileName = (string)($this->request->getData('fileName') ?: 'waypoints.kml');

        try {
            $kml = Txt2Kml::convert($text, $documentName);
        } catch (Txt2KmlFormatException $ex) {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'error' => $ex->getMessage(),
                    'line' => $ex->lineNumber,
                ]));
        }

        return $this->response
            ->withType('application/vnd.google-earth.kml+xml')
            ->withDownload($fileName)
            ->withStringBody($kml);
    }
}
