<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\File;

use Feedback;
use Tiki\FileGallery\File as TikiFile;
use Tiki\FileGallery\File;
use Tiki\Package\VendorHelper;
use Tiki\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use TikiLib;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

class DiagramHelper
{
    private const DRAW_IO_IMAGE_FORMAT = 'png';
    private const FETCH_IMAGE_CONTENTS_TIMEOUT = 5;

    private const WIKI_SYNTAX_DEFAULT_DIAGRAM_MXCELL_REPEAT = 'vertical';
    private const WIKI_SYNTAX_DEFAULT_DIAGRAM_MXCELL_OFFSET = '10';
    private const WIKI_SYNTAX_DIAGRAM_PLUGIN = 'diagram';

    /**
     * Get diagram as image given a file ID or diagram contents.
     * If the requested file or contents are cached, they will be immediately returned, otherwise they will be fetched if Tiki is configured for it.
     * @param $diagramContent
     * @return bool|false|string
     */
    public static function getDiagramAsImage($diagramContent)
    {
        global $prefs, $cachelib;
        $fileIdentifier = md5($diagramContent);
        $content = $cachelib->getCached($fileIdentifier, 'diagram');

        if (
            ! $content
            && $prefs['fgal_use_casperjs_to_export_images'] === 'y'
            && class_exists('CasperJsInstaller\Installer')
        ) {
            $content = self::getDiagramAsImageUsingCasperJs('<mxfile>' . $diagramContent . '</mxfile>', $fileIdentifier);
        }

        if (! $content && $prefs['fgal_use_drawio_services_to_export_images'] === 'y') {
            $content = self::getDiagramAsImageFromExternalService('<mxfile>' . $diagramContent . '</mxfile>');
        }

        if (! empty($content)) {
            $cachelib->cacheItem($fileIdentifier, $content, 'diagram');
        }

        return $content;
    }

    /**
     * Get an array of diagrams based on the XML content or file_id which will retrieve the File XML contents
     * @param $identifier
     * @param $page string Return specific page from the diagram
     * @return array|bool
     */
    public static function getDiagramsFromIdentifier($identifier, $page = '')
    {
        $rawXmlContent = $identifier;

        if (is_int($identifier)) {
            $file = File::id($identifier);

            if (empty($file)) {
                return false;
            }

            $rawXmlContent = $file->getContents();
        }
        $diagramRoot = simplexml_load_string($rawXmlContent);

        if ($diagramRoot === false && ! empty($identifier)) {
            Feedback::error(tr('The provided diagram XML is not valid. Please check and validate the diagram structure.'));
        }

        $diagrams = [];

        foreach ($diagramRoot->diagram as $diagram) {
            $diagramName = (string) $diagram->attributes()->name;

            if (! empty($page) && $page != $diagramName) {
                continue;
            }

            $diagrams[] = $diagram->asXML();
        }

        return $diagrams;
    }

    /**
     * Check if file is a diagram
     *
     * @param $fileId
     * @return bool
     */
    public static function isDiagram($fileId)
    {
        $file = TikiFile::id($fileId);
        $type = $file->getParam('filetype');

        if (in_array($type, ['text/plain', 'text/xml'])) {
            $data = trim($file->getContents());
            if (strpos($data, '<mx') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if needed core files exist in order to enable Diagrams
     * @return bool
     */
    public static function isPackageInstalled()
    {
        return VendorHelper::getAvailableVendorPath('diagram', '/tikiwiki/diagram/js/app.min.js') !== false;
    }

    /**
     * Parse diagram raw data
     * @param $data
     * @return string
     */
    public static function parseData($data)
    {
        return preg_replace('/\s+/', ' ', $data);
    }

    /**
     * Decode drawio's deflated diagram string
     * @param $diagramHash
     * @return string
     */
    public static function inflate(string $diagramHash): string
    {
        return gzinflate(base64_decode((string) $diagramHash));
    }

    /**
     * Inflate string in order to be drawio readable
     * @param \SimpleXMLElement $diagram_xml
     * @return string
     */
    public static function deflate(\SimpleXMLElement $diagram_xml): string
    {
        $headlessXML = str_replace("<?xml version=\"1.0\"?>\n", '', $diagram_xml->asXml());
        return base64_encode(gzdeflate($headlessXML));
    }

    /**
     * Parse Wiki Markup inside a diagram's cells
     * @param $diagram_hash
     * @return string
     * @throws \Exception
     */
    public static function parseDiagramWikiSyntax($rootDiagram): string
    {
        if (is_string($rootDiagram)) {
            $decoded = self::inflate($rootDiagram);
            $rootDiagram = simplexml_load_string(urldecode($decoded));
        }

        foreach ($rootDiagram as $root) {
            $xpathToUnset = [];
            $newCells = [];

            foreach ($root as $mxCell) {
                if (! isset($mxCell['value'])) {
                    continue;
                }

                $cellValue = (string) $mxCell['value'];
                $plugins = \WikiParser_PluginMatcher::match($cellValue);

                if ($plugins->count()) {
                    $plugin = $plugins->next();

                    if ($plugin->getName() === 'list') {
                        require_once "lib/wiki-plugins/wikiplugin_list.php";
                        $populatedListContent = [];

                        $callback = function ($result, $formatter) use (&$populatedListContent) {
                            $populatedListContent = $result;
                        };

                        $argumentParser = new WikiParser_PluginArgumentParser();
                        $listPluginArguments = $argumentParser->parse($plugin->getArguments());

                        if (! isset($listPluginArguments['diagram-repeat'])) {
                            $listPluginArguments['diagram-repeat'] = self::WIKI_SYNTAX_DEFAULT_DIAGRAM_MXCELL_REPEAT;
                        }

                        if (! isset($listPluginArguments['diagram-offset'])) {
                            $listPluginArguments['diagram-offset'] = self::WIKI_SYNTAX_DEFAULT_DIAGRAM_MXCELL_OFFSET;
                        }

                        wikiplugin_list($plugin->getBody(), array_merge($listPluginArguments, ['resultCallback' => $callback]));
                        $cellAttributes = current($mxCell->attributes());
                        $geometryAttributes = current($mxCell->mxGeometry->attributes());
                        $offsetAxis = $listPluginArguments['diagram-repeat'] == 'vertical' ? 'y' : 'x';

                        foreach ($populatedListContent as $listContent) {
                            $newCell = self::generateMxCell($cellAttributes, $geometryAttributes);
                            $newCell['value'] = htmlspecialchars_decode(TikiLib::lib('parser')->parse_data($listContent));
                            $newCells[] = $newCell;
                            $geometryAttributes[$offsetAxis] += $listPluginArguments['diagram-offset'];
                        }

                        $xpathToUnset[] = '//mxCell[@id="' . $cellAttributes['id'] . '"]';
                    } else {
                        $mxCell['value'] = htmlspecialchars_decode(TikiLib::lib('parser')->parse_data($cellValue));
                    }
                } else {
                    $cellValue = str_replace('<br>', "\r\n", $cellValue);
                    $mxCell['value'] = htmlspecialchars_decode(TikiLib::lib('parser')->parse_data($cellValue, ['is_html' => 1]));
                }
            }

            foreach ($xpathToUnset as $xpath) {
                $toUnset = current($rootDiagram->xpath($xpath));
                unset($toUnset[0]);
            }

            foreach ($newCells as $newCell) {
                XMLHelper::appendElement($root, $newCell);
            }
        }

        return self::deflate($rootDiagram);
    }

    /**
     * Converts a diagram plugin inner content to md5 (useful when performing diffs)
     * @param WikiParser_PluginMatcher $parser
     * @return bool returns true if any content was changed
     */
    public static function md5WikiPluginDiagramContent(WikiParser_PluginMatcher &$parser): bool
    {
        $hasChanges = false;
        $parser->rewind();
        while ($parser->valid()) {
            $current = $parser->current();

            if ($parser->current()->getName() !== self::WIKI_SYNTAX_DIAGRAM_PLUGIN || empty($parser->current()->getBody())) {
                $parser->next();
                continue;
            }

            $bodyHash = tr('diagram content hash - %0', md5($current->getBody()));
            $current->replaceWithPlugin(self::WIKI_SYNTAX_DIAGRAM_PLUGIN, $current->getArguments(), $bodyHash);

            $hasChanges = true;
            $parser->next();
        }

        return $hasChanges;
    }

    /**
     * Generate an mxCell based on specific attributes
     * @param $cellAttributes
     * @param $geometryAttributes
     * @return \SimpleXMLElement
     */
    private static function generateMxCell(array $cellAttributes, array $geometryAttributes): \SimpleXMLElement
    {
        $mxCell = simplexml_load_string("<mxCell><mxGeometry></mxGeometry></mxCell>");

        if (! empty($cellAttributes)) {
            foreach ($cellAttributes as $key => $attribute) {
                $mxCell[$key] = $attribute;
            }

            $mxCell['id'] = uniqid();
        }

        if (! empty($geometryAttributes)) {
            foreach ($geometryAttributes as $key => $geometryAttribute) {
                $mxCell->mxGeometry[$key] = $geometryAttribute;
            }
        }

        return $mxCell;
    }

    /**
     * Get diagram as PNG using CasperJs
     * @param $rawXml
     * @param $fileIdentifier
     * @return bool|string
     */
    private static function getDiagramAsImageUsingCasperJs($rawXml, $fileIdentifier)
    {
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

        if (file_exists($jsfile)) {
            $command = [$casperBin, $scriptPath, '--htmlfile=' . $htmlFile, '--filename=' . $fileIdentifier];

            $process = new Process($command);
            if (! empty($params['timeout'])) {
                $process->setTimeout($params['timeout']);
                $process->setIdleTimeout($params['timeout']);
            }
            try {
                $process->run();
            } catch (ProcessTimedOutException $e) {
                $e->getMessage();
                unlink($jsfile);
            }

            if ($success = $process->isSuccessful() && file_exists($imgFile)) {
                $imgData = file_get_contents($imgFile);
                unlink($jsfile);
                unlink($imgFile);
                return base64_encode($imgData);
            }
        }
    }

    /**
     * Get diagram as PNG from DRAWIO external service
     * @param $rawXml
     * @return bool|string
     */
    private static function getDiagramAsImageFromExternalService($rawXml)
    {
        global $prefs;
        $logsLib = TikiLib::lib('logs');
        $serviceEndpoint = $prefs['fgal_drawio_service_endpoint'];

        if (empty($serviceEndpoint) || filter_var($serviceEndpoint, FILTER_VALIDATE_URL) === false) {
            $logsLib->add_log('diagram export', tr('Invalid value for fgal_drawio_service_endpoint preference. Not a valid URL.'));
            return null;
        }

        $jsonPayload = json_encode([
            'format'    => self::DRAW_IO_IMAGE_FORMAT,
            'embedXml'  => '0',
            'base64'    => '1',
            'xml'       => $rawXml,
        ]);

        $client = \TikiLib::lib('tiki')->get_http_client($serviceEndpoint, [
            'timeout' => self::FETCH_IMAGE_CONTENTS_TIMEOUT
        ]);

        $client->setRawBody($jsonPayload);
        $client->setMethod(\Laminas\Http\Request::METHOD_POST);
        $client->setHeaders([
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonPayload),
        ]);

        $response = $client->send();

        $statusCode = $response->getStatusCode();

        // In case of bad requests or server issues (HTTP 4xx and 5xx)
        if (empty($statusCode) || $statusCode >= 400) {
            $logsLib->add_log(
                'diagram export',
                tr('Something went wrong when using the third party service to export the diagram. Status %0 received.', $statusCode)
            );
            return null;
        }

        return $response->getBody();
    }
}
