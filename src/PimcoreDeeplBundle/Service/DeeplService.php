<?php

/**
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) agorate GmbH (https://www.agorate.de)
 */

namespace Agorate\PimcoreDeeplBundle\Service;

use Pimcore\Model\WebsiteSetting;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeeplService
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function translate(?string $text, string $targetLocale): ?string
    {
        if (is_null($text)) {
            return null;
        }

        $authKey = $this->container->getParameter('pimcore_deepl');

        $response = $this->httpClient->request('POST', "https://api.deepl.com/v2/translate", [
            'body' => [
                'auth_key' => $authKey,
                'text' => $text,
                'target_lang' => substr($targetLocale, 0, 2)
            ]
        ]);

        $parsedResponse = json_decode($response->getContent());

        return $parsedResponse->translations[0]->text;
    }
}