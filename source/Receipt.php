<?php

namespace Fritak\eet;

use Fritak\eet\ExceptionEet;

/**
 * Receipt for EET according to v3 version.
 * 
 * @author Marek Sušický <marek.susicky@fritak.eu>
 * @version 1.0
 * @package eet
 * @link http://www.etrzby.cz/assets/cs/prilohy/EET_popis_rozhrani_v3.0_EN.pdf Documentation
 */
class Receipt
{

    /**
     * Message's UUID. The UUID shall have the format as per RFC 4122.
     * 
     * @var string 
     */
    public $uuid_zpravy;

    /**
     * First sending of sales information
     * 
     * @var boolean 
     */
    public $prvni_zaslani = TRUE;

    /**
     * Flag of verification sending mode
     * 
     * @var boolean 
     */
    public $overeni = FALSE;

    /**
     * Tax identification number
     * 
     * @var string 
     */
    public $dic_popl;

    /**
     * Appointing taxpayer tax identification number
     * 
     * @var string 
     */
    public $dic_poverujiciho;

    /**
     * Business premises ID
     * 
     * @var int 
     */
    public $id_provoz;

    /**
     * Cash register ID
     * 
     * @var int 
     */
    public $id_pokl;

    /**
     * Serial number of receipt. Length: 01-20
     * Data format mask: ^[0-9a-zA-Z\.,:;/#\-_]{1,20}$
     * 
     * @var int 
     */
    public $porad_cis;

    /**
     * Date and time of sale
     * @var \DateTime 
     */
    public $dat_trzby;

    /**
     * Total amount of sale
     * 
     * @var float 
     */
    public $celk_trzba = 0;

    /**
     * Total amount for performance exempted from VAT, other performance
     * @var float 
     */
    public $zakl_nepodl_dph = 0;

    /**
     * Total tax base ‐ basic VAT rate
     * 
     * @var float 
     */
    public $zakl_dan1 = 0;

    /**
     * Total VAT ‐ basic VAT rate
     * 
     * @var float 
     */
    public $dan1 = 0;

    /**
     * Total tax base ‐ first reduced VAT rate
     * 
     * @var float 
     */
    public $zakl_dan2 = 0;

    /**
     * Total VAT ‐ first reduced VAT rate
     * 
     * @var float 
     */
    public $dan2 = 0;

    /**
     * Total tax base ‐ second reduced VAT rate
     * 
     * @var float 
     */
    public $zakl_dan3 = 0;

    /**
     * Total VAT ‐ second reduced VAT rate
     * 
     * @var float 
     */
    public $dan3 = 0;

    /**
     * Total amount under the VAT scheme for travel service
     * 
     * @var float 
     */
    public $cest_sluz;

    /**
     * Total amount under the VAT scheme for the sale of used goods ‐ basic VAT rate
     * 
     * @var int 
     */
    public $pouzit_zboz1;

    /**
     * Total amount under the VAT scheme for the sale of used goods ‐ first reduced VAT rate
     * 
     * @var int 
     */
    public $pouzit_zboz2;

    /**
     * Total amount under the VAT scheme for the sale of used goods ‐ second reduced VAT rate
     * 
     * @var int 
     */
    public $pouzit_zboz3;

    /**
     * Total amount of payments intended for subsequent drawing or settlement
     * 
     * @var float 
     */
    public $urceno_cerp_zuct;

    /**
     * Total amount of payments which are payments subsequently drawn or settled
     * 
     * @var float 
     */
    public $cerp_zuct;

    /**
     * Sale regime
     * 
     * @var string 
     */
    public $rezim = 0;

    /**
     * Date and time of sending
     * 
     * @return int
     */
    public function getDatOdesl()
    {
        return time();
    }

    public function getHead()
    {
        return [
            'uuid_zpravy' => $this->uuid_zpravy,
            'dat_odesl' => $this->getDatOdesl(),
            'prvni_zaslani' => $this->prvni_zaslani,
            'overeni' => $this->overeni
        ];
    }

    public function getBody()
    {
        $bodyData = [
            'dic_popl'          => TRUE,
            'id_provoz'         => TRUE,
            'id_pokl'           => TRUE,
            'porad_cis'         => TRUE,
            'dat_trzby'         => TRUE,
            'celk_trzba'        => TRUE,
            'rezim'             => TRUE,
            'dic_poverujiciho'  => FAlSE,
            'zakl_nepodl_dph'   => FALSE,
            'zakl_dan1'         => FALSE,
            'dan1'              => FALSE,
            'zakl_dan2'         => FALSE,
            'dan2'              => FALSE,
            'zakl_dan3'         => FALSE,
            'dan3'              => FALSE,
            'cest_sluz'         => FALSE,
            'pouzit_zboz1'      => FALSE,
            'pouzit_zboz2'      => FALSE,
            'pouzit_zboz3'      => FALSE,
            'urceno_cerp_zuct'  => FALSE,
            'cerp_zuct'         => FALSE,
        ];

        $data = [];
        foreach ($bodyData AS $key => $mandatory)
        {
            if ($mandatory && (empty($this->$key) && $this->$key <> 0))
            {
                throw new ExceptionEet('Key "' . $key . '" is mandatory!', 701);
            }

            if (in_array($key, ['celk_trzba', 'celk_trzba', 'zakl_nepodl_dph', 'zakl_dan1', 'dan1', 'zakl_dan2', 'dan2', 'zakl_dan3', 'dan3', 'cerp_zuct', 'cest_sluz', 'urceno_cerp_zuct']))
            {
                $data[$key] = $this->numberFormatReceipt($this->$key);
                continue;
            }

            if (in_array($key, ['dat_trzby']))
            {
                $data[$key] = $this->$key->format('c');
                continue;
            }

            $data[$key] = $this->$key;
        }

        return $data;
    }

    public function getDataForControlCodes($implode = TRUE)
    {
        $array = 
        [
            $this->dic_popl,
            $this->id_provoz,
            $this->id_pokl,
            $this->porad_cis,
            $this->dat_trzby->format('c'),
            $this->numberFormatReceipt($this->celk_trzba)
        ];

        return $implode ? implode('|', $array) : $array;
    }

    public function numberFormatReceipt($value)
    {
        return number_format($value, 2, '.', '');
    }

}
