<?php

namespace Fritak\eet;

use Fritak\eet\Certificate;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

/**
 * Soap client for data message.
 * 
 * @author Marek Sušický <marek.susicky@fritak.eu>
 * @version 1.0
 * @package eet
 */
class EetClient extends \SoapClient
{

    /**
     *
     * @var Certificate 
     */
    protected $certificate;

    /**
     * 
     * @param string $wsdl
     * @param Certificate $certificate
     */
    public function __construct($wsdl, Certificate $certificate)
    {
        $this->certificate = $certificate;

        parent::__construct($wsdl, ['exceptions' => TRUE]);
    }

    public function __doRequest($request, $location, $saction, $version, $one_way = 0)
    {
        $XMLSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $DOMDocument = new \DOMDocument('1.0');

        $DOMDocument->loadXML($request);
        $WSSESoap = new \WSSESoap($DOMDocument);

        $XMLSecurityKey->loadKey($this->certificate->pkey);

        $WSSESoap->addTimestamp();
        $WSSESoap->signSoapDoc($XMLSecurityKey, ["algorithm" => XMLSecurityDSig::SHA256]);

        $binaryToken = $WSSESoap->addBinaryToken($this->certificate->cert);
        $WSSESoap->attachTokentoSig($binaryToken);

        return parent::__doRequest($WSSESoap->saveXML(), $location, $saction, $version, $one_way);
    }

}
