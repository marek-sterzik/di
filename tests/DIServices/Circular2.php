<?php

namespace Tests\DIServices;

class Circular2
{
    public function __construct(private Circular3 $circular3)
    {
    }
}
