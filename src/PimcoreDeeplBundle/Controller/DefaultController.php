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
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Db;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DefaultController extends FrontendController
{
    const TRANSLATABLE_PROPERTIES = [
        'articleExcerpt',
        'articleSubtitle',
        'articleTitle'
    ];

    public function __construct(private Document\Service $documentService,
                                private DeeplService $deeplService,
                                private Db\ConnectionInterface $db)
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
        } catch (ValidationException | Exception) {
            $newDocument->delete();
            return $this->json([
                'success' => false,
                'message' => "File $newKey already exists in $parentPath"
            ]);
        }

        $elements = $this->getTranslatableDocumentElements($newDocument);
        foreach ($elements as &$element) {
            $element['data'] = $this->deeplService->translate($element['data'], $targetLanguage);
        }

        $this->updateTranslatedElements($elements);

        $newDocumentProperties = [];
        foreach (self::TRANSLATABLE_PROPERTIES as $property) {
            $newDocumentProperty = $newDocument->getProperty($property);
            if ($newDocumentProperty === '') {
                continue;
            }
            $newDocumentProperties[] = ['data' => $this->deeplService->translate($newDocumentProperty, $targetLanguage), 'name'=>$property];
        }
        $this->updateTranslatedProperties($newDocument, $newDocumentProperties);
        $this->documentService->addTranslation($document, $newDocument, $targetLanguage);

        return $this->json([
            'success' => true,
            'message' => '',
            'id' => $newDocument->getId(),
            'key' => $newDocument->getKey()
        ]);
    }

    /**
     * @param Document $document
     * @return array
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getTranslatableDocumentElements(Document $document): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
            ->from('documents_editables', 'de')
            ->where("de.documentId = :documentId")
            ->andWhere($qb->expr()->or(
                $qb->expr()->eq('de.type', $qb->expr()->literal('input')),
                $qb->expr()->eq('de.type', $qb->expr()->literal('textarea')),
                $qb->expr()->eq('de.type', $qb->expr()->literal('wysiwyg'))
            ))
            ->setParameter("documentId", $document->getId());

        return $qb->execute()->fetchAllAssociative();
    }

    /**
     * @param $elements
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateTranslatedElements($elements): void
    {
        $db = Db::get();
        foreach ($elements as $element) {
            $identifier = [
                'documentId' => $element['documentId'],
                'name' => $element['name'],
                'type' => $element['type']
            ];
            $db->update('documents_editables', ['data' => $element['data']], $identifier);
        }
    }

    /**
     * @param Document $document
     * @param array $properties
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateTranslatedProperties(Document $document, array $properties): void
    {
        $db = Db::get();
        foreach ($properties as $property) {
            $identifier = [
                'cid' => $document->getId(),
                'ctype' => 'document',
                'type' => 'text',
                'name' => $property['name']
            ];

            $db->update('properties', ['data' => $property['data']], $identifier);
        }
    }


}
