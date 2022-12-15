<?php

/**
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) agorate GmbH (https://www.agorate.de)
 */

namespace Agorate\PimcoreDeeplBundle\Controller;

use Agorate\PimcoreDeeplBundle\Service\DeeplService;
use Exception;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Textarea;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DefaultController extends FrontendController
{
    const TRANSLATABLE_PROPERTIES = [
        'articleExcerpt',
        'articleSubtitle',
        'articleTitle'
    ];

    const TRANSLATABLE_MODEL_KEYS = [
        'Title',
        'Description'
    ];

    public function __construct(private Document\Service $documentService,
                                private DeeplService     $deeplService)
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function deeplTranslateDocument(Request $request): JsonResponse
    {
        $documentId = $request->request->get('id');
        $parentPath = $request->request->get('parent');
        $targetLanguage = $request->request->get('language');
        $document = Document::getById($documentId);

        if (is_null($document)) {
            return $this->json([
                'success' => false,
                'message' => 'Can not convert selected page to Object'
            ]);
        }

        if (empty($targetLanguage)) {
            return $this->json([
                'success' => false,
                'message' => 'No language selected'
            ]);
        }
        if (empty($parentPath)) {
            return $this->json([
                'success' => false,
                'message' => 'No Parent selected'
            ]);
        }

        $parentDocument = Document::getByPath($parentPath);

        if (is_null($parentDocument)) {
            return $this->json([
                'success' => false,
                'message' => 'Parent does not exist'
            ]);
        }

        $previousTranslations = $this->documentService->getTranslations($document);
        if (array_key_exists('de_DE', $previousTranslations)) {
            $previousTranslations['de'] = $previousTranslations['de_DE'];
        }

        if (array_key_exists($targetLanguage, $previousTranslations)) {
            return $this->json([
                'success' => false,
                'message' => 'Document already has a translation in the selected language'
            ]);
        }

        $newKey = $this->deeplService->translate($document->getKey(), $targetLanguage);
        $newDocument = $this->documentService->copyAsChild($parentDocument, $document);
        $newDocument->setKey($newKey);
        $newDocument->setPublished(false);

        try {
            $newDocument->save();
        } catch (ValidationException|Exception) {
            $newDocument->delete();
            return $this->json([
                'success' => false,
                'message' => "File $newKey already exists in $parentPath"
            ]);
        }

        $elements = $newDocument->getEditables();

        foreach ($elements as &$element) {
            if (!in_array(get_class($element), [Input::class, Textarea::class, Wysiwyg::class])) {
                continue;
            }
            /** @var Input|Textarea|Wysiwyg */
            $element->setDataFromResource($this->deeplService->translate($element->getData(), $targetLanguage));
        }

        foreach (self::TRANSLATABLE_PROPERTIES as $property) {
            $newDocumentProperty = $newDocument->getProperty($property);
            if (!is_string($newDocumentProperty) || $newDocumentProperty === '') {
                continue;
            }
            $newDocument->setProperty($property, "text", $this->deeplService->translate($newDocumentProperty, $targetLanguage));
        }

        foreach (self::TRANSLATABLE_MODEL_KEYS as $modelKey) {
            $modelKeyData = $document->{'get' . $modelKey}();
            if ($modelKeyData === '') {
                continue;
            }

            $translatedModelKeyData = $this->deeplService->translate($modelKeyData, $targetLanguage);

            $newDocument->{'set' . $modelKey}($translatedModelKeyData);
        }

        $newDocument->save();

        $this->documentService->addTranslation($document, $newDocument, $targetLanguage);

        return $this->json([
            'success' => true,
            'message' => '',
            'id' => $newDocument->getId(),
            'key' => $newDocument->getKey()
        ]);
    }
}
