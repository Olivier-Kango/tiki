<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Tiki\HeadlessBrowser\Exception\HeadlessException;
use Tiki\Package\VendorHelper;
use Tiki\Process\Process;
use WikiPlugin_Casperjs_Runner;

class Casperjs implements HeadlessBrowserInterface
{
    public function getType()
    {
        return 'casperjs';
    }

    public function getRequiredPackage()
    {
        return ['jerome-breton/casperjs-installer' => 'CasperJsInstaller\Installer'];
    }

    public function getUrlAsHtml($url, $cssSelector = null)
    {
        $script = <<<JS
            var casper = require('casper').create();
            casper.start("$url", function() {
                var html = casper.evaluate(function() {
                    return document.querySelector("$cssSelector").innerHTML;
                });
                this.echo(html);
            });
             
            casper.run();
        JS;

        $runner = new WikiPlugin_Casperjs_Runner();
        $result = $runner->run($script);
        $result = implode("\n", array_filter($result->getScriptOutput(), function ($line) {
            return ! empty($line);
        }));

        if ($result == 'null') {
            throw new HeadlessException(tr("Invalid css selector: ") . $cssSelector);
        }

        return $result;
    }

    public function getUrlAsImage($htmlFile, $outputPath = null, $cssSelector = null, $timeout = null)
    {
        $casperBin = implode(DIRECTORY_SEPARATOR, [TIKI_PATH, 'bin', 'casperjs']);
        if (! file_exists($casperBin)) {
            throw new HeadlessException(tr("Tiki needs the jerome-breton/casperjs-installer to convert webpages to image. If you do not have permission to install this package, ask the site administrator."));
        }

        $cssSelector = $cssSelector ? $cssSelector : 'body';
        $casperjsScript = <<<JS
        var casper = require('casper').create();
        var htmlFile = '{$htmlFile}';
        casper.start(htmlFile, function() {
            this.echo(this.captureBase64('png', '{$cssSelector}'));
        });
        casper.run();
        JS;

        $casperFile = writeTempFile($casperjsScript, '', true, 'wikiplugin_chart_', '.js');
        $process = new Process([$casperBin, $casperFile, '--ignore-ssl-errors=true']);
        if ($timeout) {
            $process->setTimeout($timeout);
            $process->setIdleTimeout($timeout);
        }

        try {
            $process->run(null, ['OPENSSL_CONF' => '/etc/ssl']);
        } catch (ProcessTimedOutException $e) {
            throw new HeadlessException(tr('Process timeout while capturing chart as image: ' . $e->getMessage()));
        } finally {
            unlink($casperFile);
        }

        return $process->isSuccessful() ? $process->getOutput() : '';
    }

    public function getDiagramAsImage($rawXml)
    {
        $diagramContent = str_replace(['<mxfile>', '</mxfile>'], '', $rawXml);
        $fileIdentifier = md5($diagramContent);
        $vendorPath = VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram', false);
        $casperBin = TIKI_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'casperjs';
        $scriptPath = TIKI_PATH . DIRECTORY_SEPARATOR . 'lib/jquery_tiki/tiki-diagram.js';
        $htmlFile = TIKI_PATH . DIRECTORY_SEPARATOR . 'lib/core/File/DiagramHelperExportCasperJS.html';
        $jsfile = TIKI_PATH . DIRECTORY_SEPARATOR . 'temp/do_' . $fileIdentifier . '.js';
        $imgFile = TIKI_PATH . DIRECTORY_SEPARATOR . 'temp/diagram_' . $fileIdentifier . '.png';

        if (! empty($vendorPath) && file_exists($casperBin) && file_exists($scriptPath) && file_exists($htmlFile)) {
            $jsContent = <<<EOF
            var data = {};
            data.xml = '$rawXml';
            try
            {
                render(data);
            }
            catch(e)
            {
                console.log(e);
            }
            EOF;

            if (file_exists($jsfile)) {
                unlink($jsfile);
            }

            if (file_exists($imgFile)) {
                unlink($imgFile);
            }

            file_put_contents($jsfile, $jsContent, FILE_APPEND);
        }

        if (! file_exists($jsfile)) {
            throw new HeadlessException(tr('Error capturing diagram as image: JavaScript file was not created.'));
        }

        try {
            $command = [$casperBin, $scriptPath, '--htmlfile=' . $htmlFile, '--filename=' . $fileIdentifier];
            $process = new Process($command);
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new HeadlessException(tr('Process timeout while capturing diagram as image: ' . $e->getMessage()));
        }

        if (! $process->isSuccessful() || ! file_exists($imgFile)) {
            throw new HeadlessException(tr('Error capturing diagram as image: Process was not successful.'));
        }

        $imgData = file_get_contents($imgFile);
        unlink($imgFile);
        unlink($jsfile);

        return base64_encode($imgData);
    }
}
