<?php

namespace Rariteth\LaravelCart\Contracts;

interface CartInstanceInterface
{
    /**
     * Get given cart instance name
     *
     * @return string
     */
    public function getInstance(): string;
    
    /**
     * Get cart guard name
     *
     * @return string
     */
    public function getGuard(): string;
}