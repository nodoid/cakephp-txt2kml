<?php
declare(strict_types=1);

namespace Txt2Kml;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;

/**
 * Txt2Kml plugin for CakePHP.
 *
 * Converts simple comma-delimited waypoint text into KML documents.
 * Load it in your application's bootstrap:
 *
 *     $this->addPlugin('Txt2Kml');
 *
 * The conversion logic lives in {@see \Txt2Kml\Utility\Txt2Kml} and has no
 * framework dependency, so it can also be used standalone.
 */
class Txt2KmlPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }

    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Txt2Kml',
            ['path' => '/txt2kml'],
            function (RouteBuilder $builder): void {
                $builder->fallbacks();
            },
        );

        parent::routes($routes);
    }
}
