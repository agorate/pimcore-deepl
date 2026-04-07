<?php

/**
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) agorate GmbH (https://www.agorate.de)
 */

namespace Agorate\PimcoreDeeplBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeeplService
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private HttpClientInterface   $httpClient
    )
    {
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

        $authKey = $this->parameterBag->get('pimcore_deepl');

        $response = $this->httpClient->request('POST', "https://api.deepl.com/v2/translate", [
            'body' => [
                'text' => $text,
                'target_lang' => substr($targetLocale, 0, 2)
            ],
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $authKey,
            ]
        ]);

        $parsedResponse = json_decode($response->getContent());

        return $parsedResponse->translations[0]->text;
    }
}
