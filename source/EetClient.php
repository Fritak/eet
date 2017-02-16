<?php

namespace Fritak\eet;

use DOMDocument;
use Fritak\eet\Certificate;
use RobRichards\WsePhp\WSSESoap;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SoapClient;

/**
 * Soap client for data message.
 * 
 * @author Marek Sušický <marek.susicky@fritak.eu>
 * @version 1.0
 * @package eet
 */
class EetClient extends SoapClient
{
    const TIMEOUT_INI_KEY = 'default_socket_timeout';

    /**
     *
     * @var Certificate 
     */
    protected $certificate;
    
    /**
     * Timeout in seconds
     * @var int
     */
    protected $timeout;
    
    /**
     * Connection timeout in seconds
     * @var int
     */
    protected $connectionTimeout;

    /**
     * config
     * @var array
     */
    protected $config = array();
    
    /**
     * 
     * @param string $wsdl
     * @param Certificate $certificate
     * @param int $timeout Timeout time in seconds
     * @param int $connectionTimeout Connection timeout in seconds
     */
    public function __construct($wsdl, Certificate $certificate, $timeout = FALSE, $connectionTimeout = FALSE,$config=array())
    {
        $this->certificate = $certificate;
        $this->timeout = $timeout;
        $this->connectionTimeout = $connectionTimeout;
		$this->config = $config;
        
        $opts = ['exceptions' => TRUE];
        if ($this->connectionTimeout !== FALSE){
            $opts['connection_timeout'] = $connectionTimeout;
        }
        parent::__construct($wsdl, $opts);
    }

	function libxml_display_error($error)
	{
		// from http://php.net/manual/en/domdocument.schemavalidate.php
		$return = "";
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";
				break;
		}
		$return .= trim($error->message);
		if ($error->file) {
			$return .=    " in $error->file";
		}
		$return .= " on line $error->line\n";

		return $return;
	}
    public function __doRequest($request, $location, $saction, $version, $one_way = 0)
    {
        $this->exception = FALSE;
        $XMLSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $DOMDocument = new DOMDocument('1.0');

        $DOMDocument->loadXML($request);

		if(isset($this->config["EETXMLSchema"])){
			$xml = $DOMDocument->SaveXML();
			foreach($DOMDocument->childNodes as $node1){
				foreach($node1->childNodes as $node2){
					$DOMDocument2 = new \DOMDocument;
					$inner = implode(array_map([$node2->ownerDocument,"saveHTML"], iterator_to_array($node2->childNodes)));
					$inner = str_replace("<ns1:Trzba>",'<ns1:Trzba xmlns:ns1="http://fs.mfcr.cz/eet/schema/v3">',$inner);
					$DOMDocument2->loadXML($inner);
					\libxml_use_internal_errors(true);
					if(!$DOMDocument2->schemaValidate($this->config["EETXMLSchema"])){
						// Enable user error handling
						$errors = \libxml_get_errors();
						foreach ($errors as $error) {
							$out.=$this->libxml_display_error($error);
						}
						\libxml_clear_errors();
						throw new \Fritak\eet\ExceptionEet("Unable to validate input according to schema: ".$out);
					}
				}
			}
		}
		


        $WSSESoap = new WSSESoap($DOMDocument);

        $XMLSecurityKey->loadKey($this->certificate->pkey);

        $WSSESoap->addTimestamp();
        $WSSESoap->signSoapDoc($XMLSecurityKey, ["algorithm" => XMLSecurityDSig::SHA256]);
        $binaryToken = $WSSESoap->addBinaryToken($this->certificate->cert);
        $WSSESoap->attachTokentoSig($binaryToken);

        $original = ini_get(self::TIMEOUT_INI_KEY);
		
        if ($this->timeout !== FALSE) { ini_set(self::TIMEOUT_INI_KEY, $this->timeout*1000); }
		
        $response = parent::__doRequest($WSSESoap->saveXML(), $location, $saction, $version, $one_way);
        ini_set(self::TIMEOUT_INI_KEY, $original);
        return $response;
    }
    
    public function setCertificate(Certificate $certificate){
        $this->certificate = $certificate;
    }
    
    public function getException(){
        return $this->exception;
    }
}
