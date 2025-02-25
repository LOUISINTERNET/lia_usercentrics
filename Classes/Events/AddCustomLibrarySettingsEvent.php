<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Events;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;

final class AddCustomLibrarySettingsEvent
{
    /**
     * @var PageRenderer $pageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * @var AssetCollector $assetCollector
     */
    protected AssetCollector $assetCollector;

    /**
     * @var array $usercentricsSettings
     */
    private array $usercentricsSettings;

    public function __construct(PageRenderer $pageRenderer, AssetCollector $assetCollector, array $usercentricsSettings = [])
    {
        $this->pageRenderer = $pageRenderer;
        $this->assetCollector = $assetCollector;
        $this->usercentricsSettings = $usercentricsSettings;
    }

    /**
     * Get $pageRenderer
     */
    public function getPageRenderer(): PageRenderer
    {
        return $this->pageRenderer;
    }

    /**
     * Set $pageRenderer
     *
     * @param PageRenderer $pageRenderer
     */
    public function setPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Get $assetCollector
     */
    public function getAssetCollector(): AssetCollector
    {
        return $this->assetCollector;
    }

    /**
     * Set $assetCollector
     *
     * @param AssetCollector $assetCollector
     */
    public function setAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    /**
     * Get $usercentricsSettings
     */
    public function getUsercentricsSettings(): array
    {
        return $this->usercentricsSettings;
    }
}
