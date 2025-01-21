<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use HeadlessChromium\AutoDiscover;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use Tiki\HeadlessBrowser\Exception\HeadlessException;
use Tiki\Package\VendorHelper;

class Chrome implements HeadlessBrowserInterface
{
    private $chromeBinaryPath;

    public function __construct(?string $chromeBinaryPath = null)
    {
        global $prefs;

        $this->chromeBinaryPath = $chromeBinaryPath ?? ($prefs['headlessbrowser_chrome_path'] ?? null);

        if (empty($this->chromeBinaryPath)) {
            $this->chromeBinaryPath = (new AutoDiscover())->guessChromeBinaryPath();
        }

        if (empty($this->chromeBinaryPath) || ! file_exists($this->chromeBinaryPath)) {
            throw new HeadlessException(tr('Chrome binary path not found: ') . $this->chromeBinaryPath);
        }

        if (! is_executable($this->chromeBinaryPath)) {
            throw new HeadlessException(tr('Chrome binary path is not executable: ') . $this->chromeBinaryPath);
        }
    }

    public function getType()
    {
        return 'chrome';
    }

    public function getRequiredPackage()
    {
        return [];
    }

    public function getUrlAsHtml($url, $cssSelector = null)
    {
        $html = '';

        try {
            $browserFactory = new BrowserFactory($this->chromeBinaryPath);
            $browser = $browserFactory->createBrowser([
                'headless' => true,
                'noSandbox' => true
            ]);
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation();

            if ($cssSelector) {
                $html = $page->evaluate("document.querySelector('{$cssSelector}').innerHTML")->getReturnValue();
            } else {
                $html = $page->getHtml();
            }
        } catch (\Exception $e) {
            throw new HeadlessException(tr('Error getUrlAsHtml: ' . $e->getMessage()));
        } finally {
            if (isset($browser)) {
                $browser->close();
            }
        }

        return $html;
    }

    public function getUrlAsImage($url, $outputPath = null, $cssSelector = null, $timeout = null)
    {
        $browser = null;
        $content = '';

        try {
            $browserFactory = new BrowserFactory($this->chromeBinaryPath);
            $browser = $browserFactory->createBrowser([
                'headless' => true,
                'noSandbox' => true
            ]);
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation('networkIdle', $timeout ?? 10000);

            if ($cssSelector) {
                $element = $page->dom()->querySelector($cssSelector);
                if (! $element) {
                    throw new HeadlessException("Invalid css selector {$cssSelector}");
                }
                $x = $element->getPosition()->getX();
                $y = $element->getPosition()->getY();
                $width = $element->getPosition()->getWidth();
                $height = $element->getPosition()->getHeight();
                $clip = new Clip($x, $y, $width, $height);
            } else {
                $clip = $page->getFullPageClip();
            }

            $page->screenshot([
                'captureBeyondViewport' => true,
                'clip' => $clip
            ])->saveToFile($outputPath);

            if (file_exists($outputPath)) {
                $content = base64_encode(file_get_contents($outputPath));
            }
        } catch (\Exception $e) {
            throw new HeadlessException(tr('Capture Screenshot Error: ' . $e->getMessage()));
        } finally {
            if (isset($browser)) {
                $browser->close();
            }
        }

        return $content;
    }

    public function getDiagramAsImage($rawXml)
    {
        global $tikipath;
        $diagramContent = str_replace(['<mxfile>', '</mxfile>'], '', $rawXml);
        $fileIdentifier = md5($diagramContent);
        $vendorPath = rtrim($tikipath . VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram', false), '/');
        $htmlFile = $tikipath . 'temp' . DIRECTORY_SEPARATOR . 'diagram_chrome_' . $fileIdentifier . '.html';
        $outputPath = $tikipath . 'temp' . DIRECTORY_SEPARATOR . 'diagram_chrome_' . $fileIdentifier . '.png';
        $distPath = $tikipath . NODE_PUBLIC_DIST_PATH;

        $htmlContent = <<<HTML
        <html lang="en">
        <head>
            <meta charset='utf-8'>
            <script> var diagramVendorPath = "$vendorPath/"; </script>
            <script type="text/javascript" src="$distPath/jquery/dist/jquery.min.js"></script>
            <script type="text/javascript" src="$tikipath/lib/jquery_tiki/tiki-mxgraph.js"></script>
            <script type='text/javascript' src="$vendorPath/tikiwiki/diagram/js/app.min.js"></script>
            <script type="text/javascript">
                function render() {
                    var container = document.getElementById("graph");
                    var graph_data = '$rawXml';
                    mxGraphMain(container, graph_data, null);
                }
                document.addEventListener("DOMContentLoaded", render);
            </script>
        </head>
        <body>
            <div id='graph' page="" style='height: 100%; width: 100%;'></div>
        </body>
        </html>
        HTML;

        $content = '';

        try {
            file_put_contents($htmlFile, $htmlContent);
            $url = "file://{$htmlFile}";
            $content = $this->getUrlAsImage($url, $outputPath, '#graph');
        } catch (\Exception $e) {
            throw new HeadlessException(tr('Error capturing diagram as image: ' . $e->getMessage()));
        } finally {
            unlink($htmlFile);
            unlink($outputPath);
        }

        return $content;
    }
}
