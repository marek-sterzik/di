<?php

namespace Tests\DIServices;

class Circular3
{
    public function __construct(private Circular1 $circular1)
    {
    }
}
