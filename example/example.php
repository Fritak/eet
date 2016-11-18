<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once __DIR__ . "/../vendor/autoload.php";

use Fritak\eet\ExceptionEet;
use Fritak\eet\Receipt;
use Fritak\eet\Sender;

$sender = new Sender(__DIR__ . '/config/config.json'); // load Sender with configuration

$sender->addReceipt(['uuid_zpravy' => 'b3a09b52-7c87-4014-a496-4c7a53cf9125', 'porad_cis' => 68, 'celk_trzba' => 546]);
$sender->addReceipt(['uuid_zpravy' => 'b3a09b52-7c87-4014-a496-4c7a53cf9126', 'porad_cis' => 69, 'celk_trzba' => 748]);

try {
    foreach ($sender->sendAllReceipts() AS $response)
    {
        print $response->Potvrzeni->fik . '<br />'; // Your FIK - Fiscal Identification Code ("Fiskální identifikační kód")
    }
} catch (SoapFault $e) {
    // Exception in communication
    echo $e->getMessage();
} catch (ExceptionEet $e){
    // Exception in EET
    echo $e->getMessage();
}

$receipt = new Receipt();
$receipt->uuid_zpravy = 'b3a09b52-7c87-4014-a496-4c7a53cf9125';
$receipt->porad_cis = '68';
$receipt->celk_trzba = 546;

$receipt->dic_popl = 'CZ1212121218';
$receipt->id_provoz = '273';
$receipt->id_pokl = '1';
$receipt->dat_trzby = new DateTime();

// Now we try dry run. Returns boolean TRUE/FALSE
if ($sender->dryRunSend($receipt))
{
    // Send receipt
    try {
        print $sender->send($receipt)->Potvrzeni->fik;
    } catch (SoapFault $e) {
    // Exception in communication
    echo $e->getMessage();
    } catch (ExceptionEet $e){
        // Exception in EET
        echo $e->getMessage();
    }
}
