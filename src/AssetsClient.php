<?php

namespace DerSpiegel\WoodWingAssetsClient;

use DerSpiegel\WoodWingAssetsClient\Exception\AssetsException;
use DerSpiegel\WoodWingAssetsClient\Request\AssetResponse;
use DerSpiegel\WoodWingAssetsClient\Request\BrowseRequest;
use DerSpiegel\WoodWingAssetsClient\Request\BrowseResponse;
use DerSpiegel\WoodWingAssetsClient\Request\CheckoutRequest;
use DerSpiegel\WoodWingAssetsClient\Request\CheckoutResponse;
use DerSpiegel\WoodWingAssetsClient\Request\CopyAssetRequest;
use DerSpiegel\WoodWingAssetsClient\Request\CreateFolderRequest;
use DerSpiegel\WoodWingAssetsClient\Request\CreateRelationRequest;
use DerSpiegel\WoodWingAssetsClient\Request\CreateRequest;
use DerSpiegel\WoodWingAssetsClient\Request\FolderResponse;
use DerSpiegel\WoodWingAssetsClient\Request\GetFolderRequest;
use DerSpiegel\WoodWingAssetsClient\Request\MoveRequest;
use DerSpiegel\WoodWingAssetsClient\Request\ProcessResponse;
use DerSpiegel\WoodWingAssetsClient\Request\RemoveFolderRequest;
use DerSpiegel\WoodWingAssetsClient\Request\RemoveRelationRequest;
use DerSpiegel\WoodWingAssetsClient\Request\RemoveRequest;
use DerSpiegel\WoodWingAssetsClient\Request\SearchRequest;
use DerSpiegel\WoodWingAssetsClient\Request\SearchResponse;
use DerSpiegel\WoodWingAssetsClient\Request\UpdateBulkRequest;
use DerSpiegel\WoodWingAssetsClient\Request\UpdateFolderRequest;
use DerSpiegel\WoodWingAssetsClient\Request\UpdateRequest;
use Exception;
use RuntimeException;


/**
 * Class AssetsClient
 * @package DerSpiegel\WoodWingAssetsClient
 */
class AssetsClient extends AssetsClientBase
{
    /** Assets REST API methods */


    /**
     * Search for assets
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
     * @param SearchRequest $request
     * @return SearchResponse
     */
    public function search(SearchRequest $request): SearchResponse
    {
        try {
            $response = $this->serviceRequest('search', $request->toArray());
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Search failed: <%s>', __METHOD__, $e->getMessage()), $e->getCode(),
                $e);
        }

        $this->logger->debug('Search performed',
            [
                'method' => __METHOD__,
                'query' => $request->getQ()
            ]
        );

        return (new SearchResponse())->fromJson($response);
    }


    /**
     * Browse folders and collections
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268711-Assets-Server-REST-API-browse
     * @param BrowseRequest $request
     * @return BrowseResponse
     */
    public function browse(BrowseRequest $request): BrowseResponse
    {
        try {
            $response = $this->serviceRequest('browse', $request->toArray());
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Browse failed: <%s>', __METHOD__, $e->getMessage()), $e->getCode(),
                $e);
        }

        $this->logger->debug('Browse performed',
            [
                'method' => __METHOD__,
                'path' => $request->getPath()
            ]
        );

        return (new BrowseResponse())->fromJson($response);
    }


    /**
     * Create (upload) an asset
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268771-Assets-Server-REST-API-create
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
            throw new AssetsException(sprintf('%s: Create failed: %s', __METHOD__, $e->getMessage()), $e->getCode(), $e);
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
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268971-Assets-Server-REST-API-update-check-in
     * @param UpdateRequest $request
     */
    public function update(UpdateRequest $request): void
    {
        $requestData = [
            'id' => $request->getId(),
            'parseMetadataModifications' => $request->isParseMetadataModification() ? 'true' : 'false'
        ];

        $metadata = $request->getMetadata();

        if (count($metadata) > 0) {
            $requestData['metadata'] = json_encode($metadata);
        }

        $fp = $request->getFiledata();

        if (is_resource($fp)) {
            $requestData['Filedata'] = $fp;
            $requestData['clearCheckoutState'] = $request->isClearCheckoutState() ? 'true' : 'false';
        }

        try {
            $this->serviceRequest('update', $requestData);
        } catch (Exception $e) {
            throw new AssetsException(
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

        $this->logger->info(
            sprintf(
                'Updated %s for asset <%s>',
                implode(array_intersect(['metadata', 'Filedata'], array_keys($requestData))),
                $request->getId()
            ),
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
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268991-Assets-Server-REST-API-updatebulk
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
            throw new AssetsException(
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
     * Check out asset
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851212-Assets-Server-REST-API-checkout
     * @param CheckoutRequest $request
     * @return CheckoutResponse
     */
    public function checkout(CheckoutRequest $request): CheckoutResponse
    {
        // This method is designed to do a checkout without download
        $request->setDownload(false);

        try {
            $response = $this->serviceRequest(
                sprintf('checkout/%s', urlencode($request->getId())),
                ['download' => $request->isDownload() ? 'true' : 'false']
            );
        } catch (Exception $e) {
            throw new AssetsException(
                sprintf(
                    '%s: Checkout of asset <%s> failed: %s',
                    __METHOD__,
                    $request->getId(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        $this->logger->info(sprintf('Asset <%s> checked out', $request->getId()),
            [
                'method' => __METHOD__,
                'id' => $request->getId(),
                'download' => $request->isDownload()
            ]
        );

        return (new CheckoutResponse())->fromJson($response);
    }


    /**
     * Check out and download asset
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851212-Assets-Server-REST-API-checkout
     * @param CheckoutRequest $request
     * @param string $targetPath
     */
    public function checkoutAndDownload(CheckoutRequest $request, string $targetPath): void
    {
        // This method is designed to do a checkout with download
        $request->setDownload(true);

        try {
            $response = $this->rawServiceRequest(
                sprintf('checkout/%s', urlencode($request->getId())),
                ['download' => $request->isDownload() ? 'true' : 'false']
            );

            $this->writeResponseBodyToPath($response, $targetPath);
        } catch (Exception $e) {
            throw new AssetsException(
                sprintf(
                    '%s: Checkout of asset <%s> failed: %s',
                    __METHOD__,
                    $request->getId(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        $this->logger->info(sprintf('Asset <%s> checked out and downloaded to <%s>', $request->getId(), $targetPath),
            [
                'method' => __METHOD__,
                'id' => $request->getId(),
                'download' => $request->isDownload()
            ]
        );
    }


    /**
     * Copy asset
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268731-Assets-Server-REST-API-copy
     * @param CopyAssetRequest $request
     * @return ProcessResponse
     */
    public function copyAsset(CopyAssetRequest $request): ProcessResponse
    {
        try {
            $response = $this->serviceRequest('copy', [
                'source' => $request->getSource(),
                'target' => $request->getTarget(),
                'fileReplacePolicy' => $request->getFileReplacePolicy()
            ]);
        } catch (Exception $e) {
            throw new AssetsException(
                sprintf(
                    '%s: Copy from <%s> to <%s> failed: %s',
                    __METHOD__,
                    $request->getSource(),
                    $request->getTarget(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        $this->logger->info(sprintf('Asset copied to <%s>', $request->getTarget()),
            [
                'method' => __METHOD__,
                'source' => $request->getSource(),
                'target' => $request->getTarget(),
                'fileReplacePolicy' => $request->getFileReplacePolicy()
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * Move/Rename Asset or Folder
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268891-Assets-Server-REST-API-move-rename
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
            throw new AssetsException(
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
     * Remove Assets or Collections
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851352-Assets-Server-REST-API-remove
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
                    'ids' => implode(',', $request->getIds()),
                    'folderPath' => $request->getFolderPath(),
                ]
            ));
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Remove failed', __METHOD__), $e->getCode(), $e);
        }

        $this->logger->info('Assets/Folders removed',
            [
                'method' => __METHOD__,
                'q' => $request->getQ(),
                'ids' => $request->getIds(),
                'folderPath' => $request->getFolderPath(),
                'response' => $response
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * Create a relation between two assets
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268751-Assets-Server-REST-API-create-relation
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
            throw new AssetsException(sprintf('%s: Create relation failed', __METHOD__), $e->getCode(), $e);
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
     * * Remove Assets or Collections
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851332-Assets-Server-REST-API-remove-relation
     * @param RemoveRelationRequest $request
     * @return ProcessResponse
     */
    public function removeRelation(RemoveRelationRequest $request): ProcessResponse
    {
        try {
            $response = $this->serviceRequest('removeRelation',
                [
                    'relationIds' => implode(',', $request->getRelationIds())
                ]
            );
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Remove relation failed', __METHOD__), $e->getCode(), $e);
        }

        $this->logger->info('Relations removed',
            [
                'method' => __METHOD__,
                'ids' => $request->getRelationIds(),
                'response' => $response
            ]
        );

        return (new ProcessResponse())->fromJson($response);
    }


    /**
     * Get folder metadata
     *
     * From the new Assets API (GET /api/folder/get)
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
            throw new AssetsException(sprintf('%s: Get folder failed: <%s>', __METHOD__, $e->getMessage()),
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
     * From the new Assets API (POST /api/folder)
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
            throw new AssetsException(sprintf('%s: Create folder failed: <%s>', __METHOD__, $e->getMessage()),
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
     * From the new Assets API (PUT /api/folder/{id})
     *
     * @param UpdateFolderRequest $request
     * @return FolderResponse
     */
    public function updateFolder(UpdateFolderRequest $request): FolderResponse
    {
        if (trim($request->getId()) === '') {
            throw new RuntimeException("%s: ID is empty in UpdateFolderRequest", __METHOD__);
        }

        try {
            $response = $this->apiRequest('PUT', "folder/{$request->getId()}", [
                'metadata' => (object)$request->getMetadata()
            ]);
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Update folder failed: <%s>', __METHOD__, $e->getMessage()),
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


    /**
     * Remove a folder
     *
     * From the new Assets API (DELETE /api/folder/{id})
     *
     * @param RemoveFolderRequest $request
     */
    public function removeFolder(RemoveFolderRequest $request): void
    {
        if (trim($request->getId()) === '') {
            throw new RuntimeException("%s: ID is empty in RemoveFolderRequest", __METHOD__);
        }

        try {
            $response = $this->apiRequest('DELETE', sprintf('folder/%s', $request->getId()));
        } catch (Exception $e) {
            throw new AssetsException(sprintf('%s: Remove failed', __METHOD__), $e->getCode(), $e);
        }

        $this->logger->info('Folder removed',
            [
                'method' => __METHOD__,
                'id' => $request->getId(),
                'folderPath' => $request->getPath(),
                'response' => $response
            ]
        );
    }


    /** Helper methods not part of the Assets REST API */


    /**
     * Remove asset by assetId
     *
     * @param string $assetId
     * @return ProcessResponse
     */
    public function removeById(string $assetId): ProcessResponse
    {
        return $this->removeAsset((new RemoveRequest($this->config))->setIds([$assetId]));
    }


    /**
     * Adds an asset to collection
     *
     * @param string $assetId
     * @param string $containerId
     */
    public function addToContainer(string $assetId, string $containerId): void
    {
        $request = (new CreateRelationRequest($this->getConfig()))
            ->setRelationType('contains')
            ->setTarget1Id($containerId)
            ->setTarget2Id($assetId);

        $this->createRelation($request);
    }


    /**
     * @param string $assetId
     * @param string $containerId
     * @return ProcessResponse
     */
    public function removeFromContainer(string $assetId, string $containerId): ProcessResponse
    {
        $q = $this->getRelationSearchQ(
                $containerId,
                self::RELATION_TARGET_CHILD,
                self::RELATION_TYPE_CONTAINS)
            . sprintf(' id:%s', $assetId);

        $searchRequest = (new SearchRequest($this->getConfig()))
            ->setQ($q)
            ->setMetadataToReturn(['id'])
            ->setNum(2);

        $searchResponse = $this->search($searchRequest);

        if ($searchResponse->getTotalHits() === 0) {
            return (new ProcessResponse())
                ->fromJson(['processedCount' => 0, 'errorCount' => 0]);
        }

        $relationId = $searchResponse->getHits()[0]->getRelation()['relationId'] ?? '';

        if ($relationId === '') {
            throw new AssetsException(sprintf('%s: Relation ID not found in search response', __METHOD__));
        }

        $request = (new RemoveRelationRequest($this->getConfig()))
            ->setRelationIds([$relationId]);

        $response = $this->removeRelation($request);

        $this->logger->info('Relation removed',
            [
                'method' => __METHOD__,
                'assetId' => $assetId,
                'containerId' => $containerId,
                'relationId' => $relationId
            ]
        );

        return $response;
    }


    /**
     * Creates a collection from assetPath
     *
     * @param string $assetPath Full path to collection, including .collection extension
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
     * Search for an asset by ID and return all or selected metadata
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
            throw new AssetsException(sprintf('%s: Asset with ID <%s> not found', __METHOD__, $assetId), 404);
        }

        if ($response->getTotalHits() > 1) {
            // god help us if this happens
            throw new AssetsException(sprintf('%s: Multiple assets with ID <%s> found', __METHOD__, $assetId), 404);
        }

        return $response->getHits()[0];
    }


    /**
     * Search for an asset and return its ID
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
            throw new AssetsException(sprintf('%s: No asset found for query <%s>', __METHOD__, $q), 404);
        }

        if (($response->getTotalHits() > 1) && $failIfMultipleHits) {
            throw new AssetsException(sprintf('%s: %d assets found for query <%s>', __METHOD__,
                $response->getTotalHits(), $q), 404);
        }

        return $response->getHits()[0]->getId();
    }


    /**
     * Get query for relation search
     *
     * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041854172#additional-queries
     *
     * @param string $relatedTo
     * @param string $relationTarget
     * @param string $relationType
     * @return string
     */
    public function getRelationSearchQ(
        string $relatedTo,
        string $relationTarget = '',
        string $relationType = ''
    ): string {
        $q = sprintf('relatedTo:%s', $relatedTo);

        if ($relationTarget !== '') {
            $q .= sprintf(' relationTarget:%s', $relationTarget);
        }

        if ($relationType !== '') {
            $q .= sprintf(' relationType:%s', $relationType);
        }

        return $q;
    }


    /**
     * @param AssetResponse $assetResponse
     * @param string $targetPath
     * @return void
     */
    public function downloadOriginalFile(AssetResponse $assetResponse, string $targetPath): void
    {
        if (strlen($assetResponse->getOriginalUrl()) === 0) {
            throw new AssetsException(sprintf('%s: Original URL is empty', __METHOD__), 404);
        }

        $this->downloadFileToPath($assetResponse->getOriginalUrl(), $targetPath);

        $this->logger->debug(sprintf('Original file of <%s> downloaded to <%s>', $assetResponse->getId(), $targetPath),
            [
                'method' => __METHOD__,
                'assetId' => $assetResponse->getId()
            ]
        );
    }


    /**
     * @param AssetResponse $assetResponse
     * @param string $targetPath
     */
    public function downloadOriginalFileById(AssetResponse $assetResponse, string $targetPath)
    {
        // TODO: Deprecate or fix; should be "byId" and expect a string $assetId

        $originalUrl = $assetResponse->getOriginalUrl();

        $this->downloadFileToPath($originalUrl, $targetPath);

        $this->logger->debug(sprintf('Original File Downloaded <%s>', $originalUrl),
            [
                'method' => __METHOD__,
                'assetId' => $assetResponse->getId()
            ]
        );
    }
}
