<?php

namespace Fritak\eet;

use DateTime;
use Fritak\eet\Certificate;
use Fritak\eet\EetClient;
use Fritak\eet\ExceptionEet;
use Fritak\eet\Receipt;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SoapFault;
use Traversable;

/**
 * Main Class for EET sender.
 * 
 * @author Marek Sušický <marek.susicky@fritak.eu>
 * @version 1.1.0
 * @package eet
 * @link http://www.etrzby.cz/assets/cs/prilohy/EET_popis_rozhrani_v3.0_EN.pdf Documentation
 */
class Sender
{
   
    /**
     * Config for a Sender.
     * 
     * @var array 
     */
    protected $config = NULL;

    /**
     * Config from construct.
     *
     * @var array|string
     */
    protected $configFromConstruct = NULL;
    
    /**
     * Certificate for sending.
     * 
     * @var Certificate 
     */
    protected $certificate;

    /**
     *
     * @var EetClient
     */
    protected $eetClient;
    
    /**
     * Array of Receipt
     * 
     * @var array 
     */
    protected $receipts = [];

    /**
     * @param string|array $config Path to the config file or config in array itself.
     * @return void
     */
    public function __construct($config = null)
    {
        if(!$config) $config = __DIR__ . '/config/config.json';
        $this->configFromConstruct = $config;
    }
    
    
    public function addReceipt($input)
    {
        if(!is_array($input) && !($input instanceof Receipt))
        {
            throw new ExceptionEet('Please set input - Either Receipt instance or array of the values.', 301);
        }

        $this->loadRequirements();
        
        if(is_array($input))
        {
            $receipt = new Receipt();

            if(!empty($input['uuid_zpravy']))
            {
                $receipt->uuid_zpravy = $input['uuid_zpravy'];
            }
            
            $receipt->porad_cis = $input['porad_cis'];
            $receipt->celk_trzba = $input['celk_trzba'];
            
            foreach ($this->config['defaultValues'] AS $key => $defaultValue)
            {
                $receipt->$key = isset($input[$key])? $input[$key] : $defaultValue;
            }
            
            $receipt->dat_trzby = isset($input['dat_trzby'])? $input['dat_trzby'] : new DateTime();

            $this->receipts[] = $receipt;
        }
        elseif ($input instanceof Receipt)
        {
			
            foreach ($this->config['defaultValues'] AS $key => $defaultValue)
            {
				if(!isset($input->$key)) $input->$key = $defaultValue;
            }
            if(!isset($input->dat_trzby)) $input->dat_trzby = new DateTime();
			$this->receipts[] = $input;
        }
    }

    /**
     * Process the verification mode for sending of registered sale data messages. The data message is processed in verification mode, returns value based on success.
     * 
     * @param string $service
     * @param Receipt $receipt
     * @return boolean
     */
    public function dryRunSend(Receipt $receipt)
    {
        $this->loadRequirements();

        try
        {
            $receipt->overeni = TRUE;
            $this->send($receipt);
            $receipt->overeni = FALSE;
            return TRUE;
        }
        catch (ExceptionEet $e)
        {
            $receipt->overeni = FALSE;
            return $e->getCode() == 1000? TRUE : FALSE;
        }
    }

    /**
     * Performs sending a data message to the Ministry of Finance.
     * 
     * @param Receipt $receipt
     * @return boolean|string
     * @throws ExceptionEet
     * @throws SoapFault
     */
    public function send(Receipt $receipt)
    {
        $this->loadRequirements();

        if (!$this->eetClient)
        {
            throw new ExceptionEet("No certificate provided!");
        }
        
        $data = $this->prepareDataForMessage($receipt);
        
        $receipt->pkp = base64_encode($data['KontrolniKody']['pkp']['_']);
        $receipt->bkp = $data['KontrolniKody']['bkp']['_'];

        $response = $this->eetClient->OdeslaniTrzby($data);

        if (isset($response->Chyba))
        {
            throw new ExceptionEet('EET communication error #' . $response->Chyba->kod . ' ' . ExceptionEet::$ERROR_CODE[$response->Chyba->kod], ExceptionEet::EET_CODE_OFFSET + (int) $response->Chyba->kod);
        }
        
        if(isset($response->Varovani))
        {
            throw new ExceptionEet('EET communication WARNING: #' . $response->Varovani->kod_varov . ' ' . ExceptionEet::$WARNING_CODE[$response->Varovani->kod_varov]);
        }
        
        if(isset($response->Hlavicka->bkp) && $response->Hlavicka->bkp != $data['KontrolniKody']['bkp']['_'])
        {
            throw new ExceptionEet('EET communication check error, received BKP code is wrong!', ExceptionEet::BKP_MISMATCH_CODE);
        }
        
        $receipt->fik = $response->Potvrzeni->fik;

        $response->pkp = base64_encode($data['KontrolniKody']['pkp']['_']);

        return $response;
    }
    
    /**
     * Performs sending a data message of all receipts.
     * 
     * @return array Array of responses
     * @throws ExceptionEet
     * @throws SoapFault
     */
    public function sendAllReceipts()
    {
        $this->loadRequirements();

        $return = [];
        
        foreach ($this->receipts AS $receipt)
        {
            $return[$receipt->uuid_zpravy] = $this->send($receipt);
        }
        
        return $return;
    }
    
    /**
     * Changes certificate
     * 
     * @param string $certificate Path or certificate
     * @param string $password 
     * 
     * @return void 
     */
    public function changeCertificate($certificate, $password)
    {
        if (strlen($certificate) < 2000)
        {
            $this->configFromConstruct['certificate']['path'] = $certificate;
        }
        else
        {
            $this->configFromConstruct['certificate']['certificate'] = $certificate;
        }

        if (!empty($password))
        {
            $this->configFromConstruct['certificate']['password'] = $password;
        }

        $this->loadRequirements();
    }
   
    /**
     * Changes WSDL path, reloads EetClient
     * 
     * @param string $wsdlPath
     * 
     * @return void
     */
    public function changeWsdlPath($wsdlPath)
    {
        $this->configFromConstruct['wsdlPath'] = $wsdlPath;
        $this->config['wsdlPath']              = $wsdlPath;

        if ($this->eetClient)
        {
            $this->loadEetClient();
        }
    }
    
    
    /**
     * Changes default values
     *
     * @param string $dic DIČ
     * @param int $workshopId
     * @param int $cashRegisterId
     * 
     * @return void 
     */
    public function changeDefaultValues($dic, $workshopId, $cashRegisterId)
    {
        $this->configFromConstruct['defaultValues']['dic']       = $dic;
        $this->configFromConstruct['defaultValues']['id_provoz'] = $workshopId;
        $this->configFromConstruct['defaultValues']['id_pokl']   = $cashRegisterId;
    }

    /**
     * Check if mandatory classes and extension are present.
     * 
     * @throws RequirementsException
     * @return void
     */
    protected function requirements()
    {
        if(!class_exists('\DOMDocument'))
        {
            throw new ExceptionEet('Requirements not met: please install DOMDocument.', 101);
        }
        
        if (!class_exists('\SoapClient'))
        {
            throw new ExceptionEet('Requirements not met: Please install Soap client. See http://php.net/manual/en/class.soapclient.php', 102);
        }
    }

    /**
     * Will load all needed requirements.
     */
    protected function loadRequirements()
    {
        // Check if mandatory classes and extension are present.
        $this->requirements();

        $this->loadConfig();
        if (isset($this->config['certificate']))
        {
            $this->loadCertificate();
            $this->loadEetClient();
        }
    }
    
    /**
     * Sets the config.
     *
     * @throws EetException
     */
    protected function loadConfig()
    {
        if(is_array($this->configFromConstruct) || $this->configFromConstruct instanceof Traversable)
        {
            $this->config = $this->configFromConstruct;
        }
        else if(file_exists($this->configFromConstruct))
        {
            $this->config = json_decode(file_get_contents($this->configFromConstruct), TRUE);
        }

        if(empty($this->config))
        {
            throw new ExceptionEet('Please set config - Either path to the json file or array of values.', 202);
        }
        
        if(!isset($this->config['wsdlPath']))
        {
            throw new ExceptionEet('Some of mandatory keys in config are missing.', 203);
        }
    }
    
    protected function loadCertificate()
    {
        $this->certificate = new Certificate($this->config);
    }

    /**
     * Initializes a new eet SOAP client.
     * 
     * @return void
     */
    private function loadEetClient()
    {
        $this->eetClient = new EetClient($this->config['wsdlPath'],
                                         $this->certificate,
                                         isset($this->config['timeout']) ? $this->config['timeout'] : FALSE,
                                         isset($this->config['connectionTimeout']) ? $this->config['connectionTimeout'] : FALSE,
										 $this->config);
		
		
    }

    /**
     * Get data for a message.
     * 
     * @param Receipt $receipt
     * @param boolean $check
     * @return object
     */
    private function prepareDataForMessage(Receipt $receipt)
    {
        return
        [
            'Hlavicka'      => $receipt->getHead(),
            'Data'          => $receipt->getBody(),
            'KontrolniKody' => $this->getControlCodes($receipt)
        ];
    }
    
    /**
     * Return control codes - bkp and pkp
     *
     * @param Receipt $receipt
     * @return array
     */
    public function getControlCodes(Receipt $receipt)
    {
        $securityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $securityKey->loadKey($this->certificate->pkey);

        $sig = $securityKey->signData($receipt->getDataForControlCodes());

        return 
        [
            'bkp' => 
            [
                '_' => $this->formatBkb($sig),
                'digest' => 'SHA1',
                'encoding' => 'base16'
            ],
            'pkp' => 
            [
                '_' => $sig,
                'digest' => 'SHA256',
                'cipher' => 'RSA2048',
                'encoding' => 'base64'
            ]
        ];
    }
    
    /**
     * Calculate BKB.
     * 
     * @param string $sig
     * @return string
     */
    private function formatBkb($sig)
    {
        return wordwrap(substr(sha1($sig), 0, 40) , 8 , '-' , true);
    }
}
