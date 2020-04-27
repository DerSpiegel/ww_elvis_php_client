<?php

namespace DerSpiegel\WoodWingElvisClient;

use DerSpiegel\WoodWingElvisClient\Exception\ElvisException;
use DerSpiegel\WoodWingElvisClient\Request\AssetResponse;
use DerSpiegel\WoodWingElvisClient\Request\CreateFolderRequest;
use DerSpiegel\WoodWingElvisClient\Request\CreateRelationRequest;
use DerSpiegel\WoodWingElvisClient\Request\CreateRequest;
use DerSpiegel\WoodWingElvisClient\Request\FolderResponse;
use DerSpiegel\WoodWingElvisClient\Request\GetFolderRequest;
use DerSpiegel\WoodWingElvisClient\Request\MoveRequest;
use DerSpiegel\WoodWingElvisClient\Request\ProcessResponse;
use DerSpiegel\WoodWingElvisClient\Request\RemoveRequest;
use DerSpiegel\WoodWingElvisClient\Request\SearchRequest;
use DerSpiegel\WoodWingElvisClient\Request\SearchResponse;
use DerSpiegel\WoodWingElvisClient\Request\UpdateBulkRequest;
use DerSpiegel\WoodWingElvisClient\Request\UpdateFolderRequest;
use DerSpiegel\WoodWingElvisClient\Request\UpdateRequest;
use \Exception;


/**
 * Class ElvisClient
 * @package DerSpiegel\WoodWingElvisClient
 */
class ElvisClient extends ElvisClientBase
{
    /** Elvis REST API methods */


    /**
     * Search for Elvis assets
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690386-Elvis-6-REST-API-search
     * @param SearchRequest $request
     * @return SearchResponse
     */
    public function search(SearchRequest $request): SearchResponse
    {
        try {
            $response = $this->serviceRequest('search', $request->toArray());
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Search failed: <%s>', __METHOD__, $e->getMessage()), $e->getCode(),
                $e);
        }

        $this->logger->debug('Search Performed',
            [
                'method' => __METHOD__,
                'query' => $request->getQ()
            ]
        );

        return (new SearchResponse())->fromJson($response);
    }


    /**
     * Create (upload) an asset
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690206-Elvis-6-REST-API-create
     * @param CreateRequest $request
     * @return AssetResponse
     */
    public function create(CreateRequest $request): AssetResponse
    {
        $data = $request->getMetadata();

        $fp = $request->getFiledata();

        if (is_resource($fp)) {
            $data['Filedata'] = $fp;
        }

        try {
            $response = $this->serviceRequest('create', $data);
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Create failed: %s', __METHOD__, $e->getMessage()), $e->getCode(), $e);
        }

        $assetResponse = (new AssetResponse())->fromJson($response);

        $this->logger->info('Asset created',
            [
                'method' => __METHOD__,
                'metadata' => $request->getMetadata()
            ]
        );

        return $assetResponse;
    }


    /**
     * Update an asset's metadata
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690426-Elvis-6-REST-API-update-check-in
     * @param UpdateRequest $request
     */
    public function update(UpdateRequest $request): void
    {
        // TODO: Implement fileData, clearCheckoutState

        $requestData = [
            'id' => $request->getId(),
            'metadata' => json_encode($request->getMetadata()),
            'parseMetadataModifications' => $request->isParseMetadataModification() ? 'true' : 'false'
        ];

        try {
            $this->serviceRequest('update', $requestData);
        } catch (Exception $e) {
            throw new ElvisException(
                sprintf(
                    '%s: Update failed for asset <%s> - <%s> - <%s>',
                    __METHOD__,
                    $request->getId(),
                    $e->getMessage(),
                    json_encode($requestData)
                ),
                $e->getCode(),
                $e
            );
        }

        $this->logger->info(sprintf('Updated metadata for asset <%s>', $request->getId()),
            [
                'method' => __METHOD__,
                'assetId' => $request->getId(),
                'metadata' => $request->getMetadata()
            ]
        );
    }


    /**
     * Update metadata from a bunch of assets
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690446-Elvis-6-REST-API-updatebulk
     * @param UpdateBulkRequest $request
     * @return ProcessResponse
     */
    public function updateBulk(UpdateBulkRequest $request): ProcessResponse
    {
        $requestData = [
            'q' => $request->getQ(),
            'metadata' => json_encode($request->getMetadata()),
            'parseMetadataModifications' => $request->isParseMetadataModification() ? 'true' : 'false'
        ];

        try {
            $response = $this->serviceRequest('updatebulk', $requestData);
        } catch (Exception $e) {
            throw new ElvisException(
                sprintf(
                    '%s: Update Bulk failed for query <%s> - <%s> - <%s>',
                    __METHOD__,
                    $request->getQ(),
                    $e->getMessage(),
                    json_encode($requestData)
                ),
                $e->getCode(),
                $e);
        }

        $this->logger->info(sprintf('Updated bulk for query <%s>', $request->getQ()),
            [
                'method' => __METHOD__,
                'query' => $request->getQ(),
                'metadata' => $request->getMetadata()
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * * Move/Rename Asset or Folder
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690306-Elvis-6-REST-API-move-rename
     * @param MoveRequest $request
     * @return ProcessResponse
     */
    public function move(MoveRequest $request): ProcessResponse
    {
        try {
            $response = $this->serviceRequest('move', [
                'source' => $request->getSource(),
                'target' => $request->getTarget(),
                'folderReplacePolicy' => $request->getFolderReplacePolicy(),
                'fileReplacePolicy' => $request->getFileReplacePolicy(),
                'filterQuery' => $request->getFilterQuery(),
                'flattenFolders' => $request->isFlattenFolders() ? 'true' : 'false'
            ]);
        } catch (Exception $e) {
            throw new ElvisException(
                sprintf(
                    '%s: Move/Rename from <%s> to <%s> failed: %s',
                    __METHOD__,
                    $request->getSource(),
                    $request->getTarget(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        $this->logger->info(sprintf('Asset/Folder moved to <%s>', $request->getTarget()),
            [
                'method' => __METHOD__,
                'source' => $request->getSource(),
                'target' => $request->getTarget(),
                'fileReplacePolicy' => $request->getFileReplacePolicy(),
                'folderReplacePolicy' => $request->getFolderReplacePolicy(),
                'filterQuery' => $request->getFilterQuery()
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * * Remove Assets or Collections
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002663483-Elvis-6-REST-API-remove
     * @param RemoveRequest $request
     * @return ProcessResponse
     */
    public function removeAsset(RemoveRequest $request): ProcessResponse
    {
        try {
            // filter the array, so the actual folder gets remove, not only its contents ?!
            $response = $this->serviceRequest('remove', array_filter(
                [
                    'q' => $request->getQ(),
                    'ids' => $request->getIds(),
                    'folderPath' => $request->getFolderPath(),
                ]
            ));
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Remove failed', __METHOD__), $e->getCode(), $e);
        }

        $this->logger->info(sprintf('Assets/Folders removed'),
            [
                'method' => __METHOD__,
                'q' => $request->getQ(),
                'ids' => $request->getIds(),
                'folderPath' => $request->getFolderPath(),
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * * Remove Folders
     *
     * @param string $folderId
     */
    public function removeFolder(string $folderId): void
    {
        // TODO: Use a request object
        try {
            // filter the array, so the actual folder gets remove, not only its contents ?!
            $this->apiRequest('DELETE', "folder/{$folderId}");
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Remove failed', __METHOD__), $e->getCode(), $e);
        }
    }


    /**
     * Create a relation between two assets
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002663363-Elvis-6-REST-API-create-relation
     * @param CreateRelationRequest $request
     */
    public function createRelation(CreateRelationRequest $request): void
    {
        try {
            $this->serviceRequest('createRelation',
                [
                    'relationType' => $request->getRelationType(),
                    'target1Id' => $request->getTarget1Id(),
                    'target2Id' => $request->getTarget2Id()
                ]
            );
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Create relation failed', __METHOD__), $e->getCode(), $e);
        }

        $this->logger->info(
            sprintf(
                'Relation (%s) created between <%s> and <%s>',
                $request->getRelationType(),
                $request->getTarget1Id(),
                $request->getTarget2Id()
            ),
            [
                'method' => __METHOD__,
                'relationType' => $request->getRelationType(),
                'target1Id' => $request->getTarget1Id(),
                'target2Id' => $request->getTarget2Id()
            ]
        );
    }


    /**
     * Get folder metadata
     *
     * From the new Elvis API (GET /api/folder/get)
     *
     * @param GetFolderRequest $request
     * @return FolderResponse
     */
    public function getFolder(GetFolderRequest $request): FolderResponse
    {
        try {
            $response = $this->apiRequest('GET', 'folder/get', [
                'path' => $request->getPath()
            ]);
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Get folder failed: <%s>', __METHOD__, $e->getMessage()),
                $e->getCode(), $e);
        }

        $this->logger->debug(sprintf('Folder <%s> retrieved', $request->getPath()),
            [
                'method' => __METHOD__,
                'folderPath' => $request->getPath()
            ]
        );

        return (new FolderResponse())->fromJson($response);
    }


    /**
     * Create folder with metadata
     *
     * From the new Elvis API (POST /api/folder)
     *
     * @param CreateFolderRequest $request
     * @return FolderResponse
     */
    public function createFolder(CreateFolderRequest $request): FolderResponse
    {
        try {
            $response = $this->apiRequest('POST', 'folder', [
                'path' => $request->getPath(),
                'metadata' => (object)$request->getMetadata()
            ]);
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Create folder failed: <%s>', __METHOD__, $e->getMessage()),
                $e->getCode(), $e);
        }

        $this->logger->info(sprintf('Folder <%s> created', $request->getPath()),
            [
                'method' => __METHOD__,
                'folderPath' => $request->getPath(),
                'metadata' => $request->getMetadata()
            ]
        );

        return (new FolderResponse())->fromJson($response);
    }


    /**
     * Update folder metadata
     *
     * From the new Elvis API (PUT /api/folder/{id})
     *
     * @param UpdateFolderRequest $request
     * @return FolderResponse
     */
    public function updateFolder(UpdateFolderRequest $request): FolderResponse
    {
        try {
            $response = $this->apiRequest('PUT', "folder/{$request->getId()}", [
                'metadata' => (object)$request->getMetadata()
            ]);
        } catch (Exception $e) {
            throw new ElvisException(sprintf('%s: Update folder failed: <%s>', __METHOD__, $e->getMessage()),
                $e->getCode(), $e);
        }

        $this->logger->info(sprintf('Updated metadata for folder <%s> (%s)', $request->getPath(), $request->getId()),
            [
                'method' => __METHOD__,
                'folderPath' => $request->getPath(),
                'folderId' => $request->getId()
            ]
        );

        return (new FolderResponse())->fromJson($response);
    }


    /** Helper methods not part of the Elvis REST API */


    /**
     * Remove asset by assetId
     *
     * @param string $elvisId
     * @return ProcessResponse
     */
    public function removeFromId(string $elvisId): ProcessResponse
    {
        // TODO: better name than remove*From*Id?
        return $this->removeAsset((new RemoveRequest())->setQ(sprintf("id:%s", $elvisId)));
    }


    /**
     * Adds an asset to collection
     *
     * @param string $assetId
     * @param string $containerId
     */
    public function addToContainer(string $assetId, string $containerId)
    {
        $request = (new CreateRelationRequest($this->getConfig()))
            ->setRelationType('contains')
            ->setTarget1Id($containerId)
            ->setTarget2Id($assetId);
        $this->createRelation($request);
    }


    /**
     * Creates a collection from assetPath
     *
     * @param string $assetPath
     * @param array $metadata
     * @return AssetResponse
     */
    public function createCollection(
        string $assetPath,
        array $metadata = []
    ): AssetResponse {

        $metadata['assetPath'] = $assetPath;

        return $this->create((new CreateRequest($this->getConfig()))
            ->setMetadata($metadata));
    }


    /**
     * Search for an Elvis asset by ID and return all or selected metadata
     *
     * @param string $assetId
     * @param array $metadataToReturn
     * @return AssetResponse
     */
    public function searchAsset(string $assetId, array $metadataToReturn = []): AssetResponse
    {
        $request = (new SearchRequest($this->getConfig()))
            ->setQ('id:' . $assetId);

        if (!empty($metadataToReturn)) {
            $request->setMetadataToReturn($metadataToReturn);
        }

        $response = $this->search($request);

        if ($response->getTotalHits() === 0) {
            throw new ElvisException(sprintf('%s: Asset with ID <%s> not found', __METHOD__, $assetId), 404);
        }

        if ($response->getTotalHits() > 1) {
            // god help us if this happens
            throw new ElvisException(sprintf('%s: Multiple assets with ID <%s> found', __METHOD__, $assetId), 404);
        }

        return $response->getHits()[0];
    }


    /**
     * Search for an Elvis asset and return its ID
     *
     * @param string $q
     * @param bool $failIfMultipleHits When more than asset is found: If true, raise exception. If false, return first match.
     * @return string
     */
    public function searchAssetId(string $q, bool $failIfMultipleHits): string
    {
        $request = (new SearchRequest($this->getConfig()))
            ->setQ($q)
            ->setNum(2)
            ->setMetadataToReturn(['']);

        $response = $this->search($request);

        if ($response->getTotalHits() === 0) {
            throw new ElvisException(sprintf('%s: No asset found for query <%s>', __METHOD__, $q), 404);
        }

        if (($response->getTotalHits() > 1) && $failIfMultipleHits) {
            throw new ElvisException(sprintf('%s: %d assets found for query <%s>', __METHOD__,
                $response->getTotalHits(), $q), 404);
        }

        return $response->getHits()[0]->getId();
    }


    /**
     * @param string $assetId
     * @param string $relationType
     * @return AssetResponse[]|array
     */
    public function searchRelations(string $assetId, string $relationType)
    {
        $request = (new SearchRequest($this->getConfig()))->setQ("relatedTo:$assetId relationType:$relationType");
        $response = $this->search($request);
        return $response->getHits();
    }


    /**
     * @param AssetResponse $assetResponse
     * @param string $targetPath
     * @return void
     */
    public function downloadOriginalFile(AssetResponse $assetResponse, string $targetPath): void
    {
        if (strlen($assetResponse->getOriginalUrl()) === 0) {
            throw new ElvisException(sprintf('%s: Original URL is empty', __METHOD__), 404);
        }

        $this->downloadFileByPath($assetResponse->getOriginalUrl(), $targetPath);

        $this->logger->debug(sprintf('Original File Downloaded <%s>', $assetResponse->getId()),
            [
                'method' => __METHOD__,
                'assetId' => $assetResponse->getId()
            ]
        );
    }


    /**
     * @param AssetResponse $elvisAsset
     * @param string $targetPath
     */
    public function downloadOriginalFileByElvisId(AssetResponse $elvisAsset, string $targetPath)
    {
        $originalUrl = $elvisAsset->getOriginalUrl();

        $this->downloadFileByPath($originalUrl, $targetPath);

        $this->logger->debug(sprintf('Original File Downloaded <%s>', $originalUrl),
            [
                'method' => __METHOD__,
                'assetId' => $elvisAsset->getId()
            ]
        );
    }
}
