<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Resource\Rendering;

use LIA\LiaUsercentrics\Services\Integration\UsercentricsService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\YouTubeRenderer as OriginYouTubeRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class YouTubeRenderer extends OriginYouTubeRenderer
{
    /**
     * Render for given File(Reference) html output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     *
     * @return string
     */
    public function render(FileInterface $file, $width, $height, array $options = [])
    {
        $options = $this->collectOptions($options, $file);
        $integrationService = GeneralUtility::makeInstance(UsercentricsService::class);
        if ($integrationService->isActive()) {
            $src = $this->createYouTubeUrl($options, $file);
            $attributes = $this->collectIframeAttributes($width, $height, $options);

            return $integrationService->getModifiedIframeIntegration($src, $attributes, 'YouTube Video');
        }

        return parent::render($file, $width, $height, $options);
    }
}
