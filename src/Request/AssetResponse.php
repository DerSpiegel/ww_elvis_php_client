<?php

namespace DerSpiegel\WoodWingAssetsClient\Request;


/**
 * Class AssetResponse
 *
 * @see https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
 * @package DerSpiegel\WoodWingAssetsClient\Request
 */
class AssetResponse extends Response
{
    protected string $id = '';
    protected string $permissions = '';
    protected array $metadata = [];
    protected string $highlightedText = '';
    protected string $originalUrl = '';
    protected string $previewUrl = '';
    protected string $thumbnailUrl = '';
    protected array $relation = [];


    /**
     * @param array $json
     * @return self
     */
    public function fromJson(array $json): self
    {
        if (isset($json['id'])) {
            $this->id = $json['id'];
        }

        if (isset($json['permissions'])) {
            $this->permissions = $json['permissions'];
        }

        if (isset($json['metadata']) && is_array($json['metadata'])) {
            $this->metadata = $json['metadata'];
        }

        if (isset($json['highlightedText'])) {
            $this->highlightedText = $json['highlightedText'];
        }

        if (isset($json['originalUrl'])) {
            $this->originalUrl = $json['originalUrl'];
        }

        if (isset($json['previewUrl'])) {
            $this->previewUrl = $json['previewUrl'];
        }

        if (isset($json['thumbnailUrl'])) {
            $this->thumbnailUrl = $json['thumbnailUrl'];
        }

        if (isset($json['relation']) && is_array($json['relation'])) {
            $this->relation = $json['relation'];
        }

        return $this;
    }


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }


    /**
     * @return string
     */
    public function getPermissions(): string
    {
        return $this->permissions;
    }


    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }


    /**
     * @return string
     */
    public function getHighlightedText(): string
    {
        return $this->highlightedText;
    }


    /**
     * @return string
     */
    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }


    /**
     * @return string
     */
    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }


    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }


    /**
     * @return array
     */
    public function getRelation(): array
    {
        return $this->relation;
    }
}
