<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Tabular extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $data = $this->obj->getData();

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();

        if (! isset($data['name'], $data['tracker'])) {
            return false;
        }

        return true;
    }

    public function _install()
    {
        $data = $this->getData();
        $this->replaceReferences($data);

        $lib = TikiLib::lib('tabular');
        $tabularId = $lib->create($data['name'], $data['tracker']);

        $reference_converter = function($field){
            if (is_numeric($field['field'])) {
                if ($trk_field = TikiLib::lib('trk')->get_field_info($field['field'])) {
                    $field['field'] = $trk_field['permName'];
                }
            }
            foreach (['isPrimary', 'isReadOnly', 'isExportOnly', 'isUniqueKey'] as $param) {
                $field[$param] = $field[$param] === 'y';
            }
            return $field;
        };

        $data['fields'] = array_map($reference_converter, $data['fields']);
        $data['filters'] = array_map($reference_converter, $data['filters']);

        $info = $lib->getInfo($tabularId);
        $info['format_descriptor'] = $data['fields'];
        $info['filter_descriptor'] = $data['filters'];

        $definition = Tracker_Definition::get($data['tracker'], false);
        $schema = new \Tracker\Tabular\Schema($definition);
        $schema->loadFormatDescriptor($info['format_descriptor']);
        $schema->loadFilterDescriptor($info['filter_descriptor']);
        $schema->loadConfig($info['config']);

        $config = ! empty($data['config']) ? $data['config'] : [];
        $odbc_config = ! empty($data['odbc_config']) ? $data['odbc_config'] : [];
        $api_config = ! empty($data['api_config']) ? $data['api_config'] : [];

        $lib->update($tabularId, $info['name'], $schema->getFormatDescriptor(), $schema->getFilterDescriptor(), $config, $odbc_config, $api_config);

        return $tabularId;
    }

    /**
     * Export import-export formats
     *
     * @param Tiki_Profile_Writer $writer
     * @param int|array $tabularIds
     * @param bool $all
     * @return bool
     * @throws Exception
     */
    public static function export(Tiki_Profile_Writer $writer, $tabularIds, $all = false)
    {
        $lib = TikiLib::lib('tabular');

        if (isset($tabularIds) && ! $all) {
            $tabulars = [];
            $tabularIds = (array) $tabularIds;

            foreach ($tabularIds as $tabularId) {
                $tabulars[] = ['tabularId' => $tabularId];
            }
        } else {
            $tabulars = $lib->getList();
        }

        foreach ($tabulars as $tabular) {
            $tabularId = $tabular['tabularId'];
            $info = $lib->getInfo($tabularId);

            if (empty($info['tabularId'])) {
                return false;
            }

            $definition = Tracker_Definition::get($info['trackerId']);
            $reference_converter = function($field) use ($writer, $definition) {
                $field = Tiki_Profile::convertYesNo($field);
                if ($trk_field = $definition->getFieldFromPermName($field['field'])) {
                    $field['field'] = $writer->getReference('tracker_field', $trk_field['fieldId']);
                }
                return $field;
            };

            $fields = array_map($reference_converter, $info['format_descriptor']);
            $filters = array_map($reference_converter, $info['filter_descriptor']);

            $data = [
                'name' => $info['name'],
                'tracker' => $writer->getReference('tracker', $info['trackerId']),
                'fields' => $fields,
                'filters' => $filters,
                'config' => $info['config'],
                'odbc_config' => $info['odbc_config'],
                'api_config' => $info['api_config'],
            ];

            $writer->addObject('tabular', $tabularId, $data);
        }

        return true;
    }
}
