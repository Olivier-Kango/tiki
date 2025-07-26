<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for PeerTube video integration
 *
 * Letter key: ~peertube~
 *
 */

namespace Tiki\Tracker\Field;

use Tracker\Field\AbstractItemField;
use Tracker\Field\SynchronizableInterface;
class TrackerFieldPeerTube extends AbstractItemField implements SynchronizableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'peertube' => [
                'name' => tr('PeerTube Video'),
                'description' => tr('Display a series of attached PeerTube videos.'),
                'help' => 'PeerTube',
                'prefs' => ['trackerfield_peertube', 'feature_peertube', 'wikiplugin_peertube'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters for the {peertube} plugin, e.g., width=800&height=600'),
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters for the {peertube} plugin, e.g., width=240&height=80'),
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $insertId = $this->getInsertId();

        if (isset($requestData[$insertId])) {
            $value = implode(',', $requestData[$insertId]);
        } elseif (! empty($requestData['old_' . $insertId])) { // All entries removed
            $value = '';
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        global $tikilib;
        $peertubelib = $tikilib->getLib('peertubeuser');
        if (! $peertubelib) {
            return tr('PeerTube library not available.');
        }
        $videos = array_filter(explode(',', $this->getValue()));
        $videoList = $peertubelib->getVideoList($videos);

        return $this->renderTemplate(
            'trackerinput/peertube.tpl',
            $context,
            [
                'videos' => $videoList,
                'extras' => array_diff($videos, array_column($videoList, 'id')),
            ]
        );
    }

    public function renderOutput($context = [])
    {
        $params = $context['list_mode'] === 'y' ? $this->getOption('displayParamsForLists', []) : $this->getOption('displayParams', []);
        parse_str($params, $params);

        include_once 'lib/wiki-plugins/wikiplugin_peertube.php';
        $videoIds = array_filter(explode(',', $this->getValue()));
        $output = '';

        foreach ($videoIds as $id) {
            $output .= wikiplugin_peertube('', array_merge($params, ['id' => $id]));
        }

        return $output;
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }
}

class_alias(TrackerFieldPeerTube::class, 'Tracker_Field_PeerTube');
