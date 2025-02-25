<?php

defined('TYPO3') || die;

(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][\LIA\LiaUsercentrics\Hooks\PageRendererPreProcessHook::class]
        = \LIA\LiaUsercentrics\Hooks\PageRendererPreProcessHook::class . '->addLibrary';

    // Override Resource Renderer - BEGING
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer::class] = [
        'className' => LIA\LiaUsercentrics\Resource\Rendering\VimeoRenderer::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Rendering\YouTubeRenderer::class] = [
        'className' => LIA\LiaUsercentrics\Resource\Rendering\YouTubeRenderer::class,
    ];
    // Override Resource Renderer - END
})();
