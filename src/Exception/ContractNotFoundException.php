<?php

namespace App\Exception;

use Exception;

class ContractNotFoundException extends Exception
{
    const ERROR_CODE = 'contract_not_found';

    public function __construct($id, $code = 0, Exception $previous = null) {
        $message = "The contract %s does not exist";
        parent::__construct(sprintf($message, $id), $code, $previous);
    }
}
