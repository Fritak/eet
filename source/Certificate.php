<?php

namespace Fritak\eet;

use Fritak\eet\ExceptionEet;

/**
 * Parse PKCS#12 file and store X.509 certificate and private key.
 * 
 * @author Marek Sušický <marek.susicky@fritak.eu>
 * @version 1.0
 * @package eet
 */
class Certificate
{

    /**
     * Certificate key
     * 
     * @var string
     */
    public $pkey;

    /**
     * Certificate X.509
     * 
     * @var string
     */
    public $cert;

    /**
     * 
     * @param array $config
     */
    public function __construct($config)
    {
        if (!file_exists($config['certificate']['path']))
        {
            throw new ExceptionEet("Missing certificate file!", 304);
        }

        $certs = [];
        $pkcs12 = file_get_contents($config['certificate']['path']);

        if (!openssl_pkcs12_read($pkcs12, $certs, $config['certificate']['password']))
        {
            throw new ExceptionEet("Failed to import PKCS#12 certificate! Please check your password.", 305);
        }

        $this->pkey = $certs['pkey'];
        $this->cert = $certs['cert'];
    }

}
