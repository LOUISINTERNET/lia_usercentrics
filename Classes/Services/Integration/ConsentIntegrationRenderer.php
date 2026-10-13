<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Services\Integration;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\TypoScript\ReaderService;
use LIA\LiaUsercentrics\Services\TypoScript\TypoScriptValue;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

final readonly class ConsentIntegrationRenderer
{
    public function __construct(
        private ReaderService $typoscriptReader,
        private PageRenderer $pageRenderer,
        private AssetCollector $assetCollector,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws LiaUsercentricsException
     */
    public function isEnabled(): bool
    {
        $useConsentIntegration = TypoScriptValue::getString($this->typoscriptReader->getUsercentricsSettings(), 'useConsentIntegration');
        if (!in_array($useConsentIntegration, ['', '0', '1'], true)) {
            throw new LiaUsercentricsException('The value of the constant useConsentIntegration is not valid. It can be only 0 or 1.', 1791905588);
        }

        return $useConsentIntegration === '1';
    }

    /**
     * @throws LiaUsercentricsException
     */
    public function render(ServerRequestInterface $request): void
    {
        $configuration = ConsentIntegrationConfiguration::fromSettings($this->typoscriptReader->getUsercentricsSettings());

        $this->assetCollector->addInlineJavaScript(
            'lia-usercentrics-config',
            $configuration->toJson(),
            ['type' => 'application/json', 'id' => 'lia-usercentrics-config'],
            ['priority' => true]
        );
        $this->assetCollector->addJavaScript(
            'lia-usercentrics-consent-integration',
            'EXT:lia_usercentrics/Resources/Public/JavaScript/consent-integration.js',
            ['type' => 'module'],
            ['priority' => true]
        );
        $this->assetCollector->addStyleSheet(
            'lia-usercentrics-consent-placeholder',
            'EXT:lia_usercentrics/Resources/Public/Css/consent-placeholder.css'
        );
        $this->pageRenderer->addFooterData($this->renderPlaceholderTemplate($request));
    }

    /**
     * @throws LiaUsercentricsException
     */
    private function renderPlaceholderTemplate(ServerRequestInterface $request): string
    {
        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: $this->getTemplateRootPaths(),
            request: $request,
        ));

        return $view->render('ConsentPlaceholder');
    }

    /**
     * @return array<int, string>
     * @throws LiaUsercentricsException
     */
    private function getTemplateRootPaths(): array
    {
        $view = TypoScriptValue::getArray($this->typoscriptReader->getUsercentricsConfiguration(), 'view');
        $templateRootPaths = [];
        foreach (TypoScriptValue::getArray($view, 'templateRootPaths') as $key => $path) {
            if (!is_string($path) || !is_numeric($key)) {
                throw new LiaUsercentricsException(
                    sprintf('The entry "%s" in plugin.tx_liausercentrics.view.templateRootPaths is not valid, expected a numeric key and a path.', $key),
                    1791905589
                );
            }

            $templateRootPaths[(int)$key] = $path;
        }

        if ($templateRootPaths === []) {
            throw new LiaUsercentricsException('plugin.tx_liausercentrics.view.templateRootPaths is empty. Make sure that the static template of the extension is loaded.', 1791905590);
        }

        ksort($templateRootPaths);

        return $templateRootPaths;
    }
}
