<?php

namespace Fritak\eet;

class ExceptionEet extends \Exception 
{
    public static $WARNING_CODE = 
    [
        1 => "DIC poplatnika v datove zprave se neshoduje s DIC v certifikatu (The taxpayer identification codes (DIČ) in the message and certificate differ)",
        2 => "Chybny format DIC poverujiciho poplatnika (Invalid structure of tax identification number of the appointing taxpayer)",
        3 => "Chybna hodnota PKP (Invalid value of Taxpayer’s signature code (PKP))",
        4 => "Datum a cas prijeti trzby je novejsi nez datum a cas prijeti zpravy (The date and time of sale is newer than the date and time of data message acceptance)",
        5 => "Datum a cas prijeti trzby je vyrazne v minulosti ",
    ];
    
    public static $ERROR_CODE = 
    [
        -1 => 'Docasna technicka chyba zpracovani – odeslete prosim datovou zpravu pozdeji (Temporary technical error in processing - please re-send the data message later)',
        0  => 'Datovou zpravu evidovane trzby v overovacim modu se podarilo zpracovat (The registered sale data message in verification mode was successfully processed)',
        1  => '',
        2  => 'Kodovani XML neni platne (The XML encoding is not valid)',
        3  => 'XML zprava nevyhovela kontrole XML schematu (The XML message failed the XML schema check)',
        4  => 'Neplatny podpis SOAP zpravy (Invalid SOAP message signature) ',
        5  => 'Neplatny kontrolni bezpecnostni kod poplatnika (BKP) (Invalid Taxpayer\'s Security Code (BKP))',
        6  => 'DIC poplatnika ma chybnou strukturu (Invalid structure of tax identification number)',
        7  => 'Datova zprava je prilis velka (The data message is too big) ',
        8  => 'Datova zprava nebyla zpracovana kvuli technicke chybe nebo chybe dat (The data message was not processed because of a technical error or a data error) ',
    ];
}
