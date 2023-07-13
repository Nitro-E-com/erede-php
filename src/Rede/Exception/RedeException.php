<?php

namespace Rede\Exception;

use Rede\Transaction;
use RuntimeException;
use Throwable;

class RedeException extends RuntimeException
{
    private ?string $response = null;

    private ?Transaction $transaction = null;

    public function __construct($message = "", $code = 0, ?Throwable $previous = null, string $response = null, ?Transaction $transaction = null)
    {
        parent::__construct($message, $code, $previous);

        $this->response = $response;
        $this->transaction = $transaction;
    }

    /**
     * @return string|null
     */
    public function getResponse(): ?string
    {
        return $this->response;
    }

    /**
     * @return Transaction|null
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }
}
