<?php

namespace Rariteth\LaravelCart\Contracts;

interface CartInterface
{
    /**
     * Get given  cart instance name
     *
     * @return string
     */
    public function getInstance(): string;
}