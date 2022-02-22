<?php

/**
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) agorate GmbH (https://www.agorate.de)
 */

namespace Agorate\PimcoreDeeplBundle;

use PackageVersions\Versions;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreDeeplBundle extends AbstractPimcoreBundle implements PimcoreBundleInterface
{
    use PackageVersionTrait;
    const PACKAGE_NAME = 'agorate/pimcore-deepl';

    /**
     * Returns all used JavaScript files
     *
     * @return string[]
     */
    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoredeepl/js/deepl-translation/startup.js'
        ];

    }

    /**
     * Bundle name as shown in extension manager
     *
     * @return string
     */
    public function getNiceName(): string
    {
        return 'agorate - Pimcore Deepl Bundle';
    }

    /**
     * Bundle description as shown in extension manager
     *
     * @return string
     */
    public function getDescription(): string
    {
        return "Bundle to translate (currently just documents) via deepl";
    }

    /** normalizes version to pretty version
     * e. g. v2.3.0@9e016f4898c464f5c895c17993416c551f1697d3 to 2.3.0
     *
     * @return array|string|null
     */
    public static function getSolutionVersion(): array|string|null
    {
        $version = Versions::getVersion(self::PACKAGE_NAME);

        $version = preg_replace('/^v/', '', $version);
        return preg_replace('/@(.+)$/', '', $version);
    }

    /**
     * Returns the composer package name used to resolve the version
     *
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}