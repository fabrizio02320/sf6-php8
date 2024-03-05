<?php

namespace App\Exception;

use Exception;

class ReceiptNotFoundException extends Exception
{
    const ERROR_CODE = 'receipt_not_found';

    public function __construct($id, $code = 0, Exception $previous = null) {
        $message = "The receipt %s does not exist";
        parent::__construct(sprintf($message, $id), $code, $previous);
    }
}
