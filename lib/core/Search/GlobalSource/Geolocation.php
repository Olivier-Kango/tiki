<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_GlobalSource_Geolocation implements Search_GlobalSource_Interface
{
    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        if (isset($data['geo_location'])) {
            return false;
        }

        $geolib = TikiLib::lib('geo');
        $coordinates = $geolib->get_coordinates_string($objectType, $objectId);

        $coordsArray = (array) $geolib->get_coordinates($objectType, $objectId);

        if (isset($coordsArray['lat'], $coordsArray['lon'])) {
            unset($coordsArray['zoom']);
            $coordsArray['lat'] = (float) $coordsArray['lat'];
            $coordsArray['lon'] = (float) $coordsArray['lon'];
        } else {
            $coordsArray = null;
        }

        $alreadyLocated = isset($data['geo_located']) && $data['geo_located'] == 'y';

        return [
            'geo_located' => $typeFactory->identifier(($coordinates || $alreadyLocated) ? 'y' : 'n'),
            'geo_location' => $typeFactory->identifier($coordinates),
            'geo_point' => $typeFactory->geopoint($coordsArray),
        ];
    }

    public function getProvidedFields(): array
    {
        return [
            'geo_located',
            'geo_location',
            'geo_point',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'geo_located' => 'identifier',
            'geo_location' => 'identifier',
            'geo_point' => 'geopoint',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
        ];
    }
}
