<?php

namespace Tests\DIServices;

class C2
{
    private ?C1 $c1 = null;

    public function __construct()
    {
    }

    public function setC1(C1 $c1): self
    {
        $this->c1 = $c1;
        return $this;
    }

    public function getC1(): ?C1
    {
        return $this->c1;
    }
}

