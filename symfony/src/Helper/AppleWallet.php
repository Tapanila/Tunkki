<?php

namespace App\Helper;


use PKPass\PKPass;

class AppleWallet
{
    public function getPass(
        string $barcode,
        float $latitude,
        float $longitude,
        string $header,
        string $primaryString): string
    {

        $certFile = \dirname(__DIR__) . '/../config/secrets/' . $_ENV['APP_ENV'] . '/apple-wallet.p12';

        $pass = new PKPass($certFile, $_ENV['APPLE_PASS_CERTIFICATE_PASSWORD']);

        // Pass content
        $data = [
            'description' => $header,
            'formatVersion' => 1,
            'organizationName' => 'Entropy',
            'passTypeIdentifier' => 'pass.fi.entropy.wallet',
            'serialNumber' => $barcode,
            'teamIdentifier' => $_ENV['APPLE_PASS_TEAM_IDENTIFIER'],
            'locations' => [
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ],
            'barcode' => [
                'format' => 'PKBarcodeFormatCode128',
                'message' => $barcode,
                'messageEncoding' => 'iso-8859-1',
            ],
            'eventTicket' => [
                'headerFields' => [
                    [
                        'key' => 'passHeader',
                        'value' => $header
                    ]
                    ],
                'primaryFields' => [
                    [
                        'key' => 'name',
                        'value' => $primaryString
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
