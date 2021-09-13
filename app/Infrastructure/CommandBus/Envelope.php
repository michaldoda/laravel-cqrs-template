<?php

namespace App\Infrastructure\CommandBus;


class Envelope
{
    private $data;
    /**
     * Envelope constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
