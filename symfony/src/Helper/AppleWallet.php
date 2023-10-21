<?php

namespace App\Helper;

use PKPass\PKPass;

class AppleWallet
{
    public function getPass(
        $barcode,
        $latitude = 60.187340,
        $longitude = 24.836350,
        $header = 'Kerhohuone',
        $primaryString = ''): string
    {
        $certFile = \dirname(__DIR__) . '/../assets/certificates/EntropyTunkkiCertificates.p12';

        $pass = new PKPass($certFile, $_ENV['APPLE_PASS_CERTIFICATE_PASSWORD']);

        // Pass content
        $data = [
            'description' => 'Kerhohuone',
            'formatVersion' => 1,
            'organizationName' => 'Entropy',
            'passTypeIdentifier' => 'pass.fi.entropy.wallet',
            'serialNumber' => strval($barcode),
            'teamIdentifier' => 'Q5J96ZZSLK',
            'locations' => [
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ],
            'barcode' => [
                'format' => 'PKBarcodeFormatCode128',
                'message' => strval($barcode),
                'messageEncoding' => 'iso-8859-1',
            ],
            'eventTicket' => [
                'headerFields' => [
                    [
                        'key' => 'passHeader',
                        'value' => strval($header)
                    ]
                    ],
                'primaryFields' => [
                    [
                        'key' => 'name',
                        'value' => strval($primaryString)
                    ]
                ]
            ],
            
            'logoText' => 'Entropy',
        ];
        $pass->setData($data);

        $pass->addFile('images/logo.png','icon.png');
        $pass->addFile('images/logo.png');
        $pass->addfile('images/placeholders/event.png', 'background.png');
        
        // Create and output the pass
        return $pass->create(true);
    }
}
