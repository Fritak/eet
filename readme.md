API for EET Client in PHP
========================

This code is an implementation of the EET ("elektronická evidence tržeb") in a PHP. Be aware that this library is working even from your localhost, if you have an internet connection. 

INSTALLATION
------------

```
composer require "fritak/eet"
```

REQUIREMENTS
------------
The minimum requirement is PHP 5.6 on your Web Server.
Prerequisite are these libraries and php extensions: 
* robrichards/wse-php
* robrichards/xmlseclibs
* ramsey/uuid
* Soap client. See http://php.net/manual/en/soap.setup.php
* Open SSL. See http://php.net/manual/en/openssl.setup.php


## SETUP
Example of config:
```json
{
    "certificate": {
        "path": "./certificate/01000003.p12",
        "password": "eet"
    },
    "wsdlPath": "./soapFiles/EETServiceSOAP.wsdl",
    "defaultValues": {
        "dic_popl": "CZ1212121218",
        "id_provoz": "273",
        "id_pokl": "1"
    },
    "timeout": 10,
    "connectionTimeout": 3
}
```
* Move certificate (PKCS#12) to your path. (See information on how to get one, or use the certificate from "/example/certificate" for playground - TEST only)
* Set path to wsdl file for EET (you need to include XSD schema too).

Note: 
* Example file for playground (EETServiceSOAP.wsdl) is v3 from [http://www.etrzby.cz](http://www.etrzby.cz/assets/cs/prilohy/EETServiceSOAP.wsdl) 
and XSD from [http://www.etrzby.cz](http://www.etrzby.cz/assets/cs/prilohy/EETXMLSchema.xsd)


## BASIC USAGE
```php
use Fritak\eet\Sender;

$sender = new Sender(__DIR__ . '/config.json'); // load Sender with configuration

$sender->addReceipt(['uuid_zpravy' => 'b3a09b52-7c87-4014-a496-4c7a53cf9125', 'porad_cis' => 68, 'celk_trzba' => 546]);

// You can let uuid_zpravy empty, it will be  automatically generated
$sender->addReceipt(['porad_cis' => 69, 'celk_trzba' => 748]);

foreach($sender->sendAllReceipts() AS $response)
{
    $response->Potvrzeni->fik; // Your FIK - Fiscal Identification Code ("Fiskální identifikační kód")
}     
```

## Change certificate or defalut values later on
```php
$sender->changeCertificate($certificate, $password);
$sender->changeDefaultValues($dic, $workshopId, $cashRegisterId);
```


## ADVANCED USAGE - Receipt
```php
use Fritak\eet\Sender;
use Fritak\eet\Receipt;

$sender = new Sender(__DIR__ . '/config.json'); // load Sender with configuration

$receipt = new Receipt();
$receipt->uuid_zpravy = 'b3a09b52-7c87-4014-a496-4c7a53cf9125'; // Or empty, it will be  automatically generated
$receipt->porad_cis   = '68';
$receipt->celk_trzba  = 546;

$receipt->dic_popl    = 'CZ1212121218';
$receipt->id_provoz   = '273';
$receipt->id_pokl     = '1';
$receipt->dat_trzby   = new \DateTime();

// Now we try dry run. Returns boolean TRUE/FALSE
if ($sender->dryRunSend($receipt))
{
    // Send receipt
    $fik = $sender->send($receipt)->Potvrzeni->fik;
}
```

## Nette integration
Nette Framework is an open-source framework for creating web applications in PHP 5 and 7. There is basic integration into your application.

Include library to your project.
Include config files to your parameters.neon:
```json
parameters:
	senderEetParameters:
		certificate:
			path: ''
			password: ''
		wsdlPath : ''
		defaultValues:
			dic_popl: ''
			id_provoz: ''
			id_pokl: ''
services:
	senderEet: Fritak\eet\Sender(%senderEetParameters%)
```

That's it! Now you can use it as noted above, for example action in presenter:
```php
$sender = $this->context->getService('senderEet');
$sender->addReceipt(['porad_cis' => 85, 'celk_trzba' => 9875]);

foreach($sender->sendAllReceipts() AS $response)
{
    $response->Potvrzeni->fik;
}
```

## Information
* [eTrzby](http://www.etrzby.cz/) Information
* [eTrzby](http://www.etrzby.cz/cs/novinky_autentizacni-udaje-k-evidenci-trzeb) - How to get certificate
* [technicka-specifikace](http://www.etrzby.cz/cs/technicka-specifikace) Technická specifikace
* [soap](http://php.net/manual/en/soap.setup.php)
* [openssl](http://php.net/manual/en/openssl.setup.php)