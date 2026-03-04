<?php

namespace Tests\DIServices;

class Circular1
{
    public function __construct(private Circular2 $circular2)
    {
    }
}
