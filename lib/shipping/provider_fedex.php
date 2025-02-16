<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class ShippingProvider_Fedex implements ShippingProvider
{
    public $account;
    private $key;
    private $password;
    private $meter;

    public function __construct(array $config)
    {
        $this->key = $config['key'];
        $this->password = $config['password'];
        $this->meter = $config['meter'];
    }

    public function getRates(array $from, array $to, array $packages)
    {
        if (! class_exists('SoapClient')) {
            return [];
        }

        $wsdl = __DIR__ . '/FedEx_v8.wsdl';
        $args = [];

        $request = $this->getRequest($from, $to, $packages);

        try {
            $client = new SoapClient($wsdl, $args);
            $response = $client->getRates($request);

            $options = $response->RateReplyDetails;
            $out = $this->extractRates($options);

            return $out;
        } catch (SoapFault $e) {
            return [];
        }
    }

    private function extractRates($options)
    {
        $out = [];

        foreach ($options as $option) {
            if ($detail = reset($option->RatedShipmentDetails)) {
                $charge = $detail->ShipmentRateDetail->TotalNetCharge;
                $out[] = [
                    'provider' => 'FedEx',
                    'service' => $option->ServiceType,
                    'readable' => tra($option->ServiceType),
                    'cost' => number_format($charge->Amount, 2, '.', ''),
                    'currency' => $charge->Currency,
                ];
            }
        }

        return $out;
    }

    private function getRequest($from, $to, $packages)
    {
        $request = [
            'WebAuthenticationDetail' => [
                'UserCredential' => [
                    'Key' => $this->key,
                    'Password' => $this->password,
                ],
            ],
            'ClientDetail' => [
                'AccountNumber' => $this->account,
                'MeterNumber' => $this->meter,
            ],
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '8',
                'Intermediate' => '0',
                'Minor' => '0',
            ],
            'RequestedShipment' => [
                'PackagingType' => 'YOUR_PACKAGING',
                'Shipper' => $this->buildAddress($from),
                'Recipient' => $this->buildAddress($to),
                'RateRequestTypes' => 'LIST',
                'PackageDetail' => 'INDIVIDUAL_PACKAGES',
                'RequestedPackageLineItems' => array_map([ $this, 'buildPackage' ], $packages),
            ],
        ];

        return $request;
    }

    private function buildAddress($address)
    {
        return [
            'Address' => [
                'PostalCode' => $address['zip'],
                'CountryCode' => $address['country'],
            ],
        ];
    }

    private function buildPackage($package)
    {
        return [
            'Weight' => [
                'Value' => $package['weight'],
                'Units' => 'KG',
            ],
        ];
    }
}
