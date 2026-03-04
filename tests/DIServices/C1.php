<?php

namespace Tests\DIServices;

class C1
{
    private ?C2 $c2 = null;

    public function __construct()
    {
    }

    public function setC2(C2 $c2): self
    {
        $this->c2 = $c2;
        return $this;
    }

    public function getC2(): ?C2
    {
        return $this->c2;
    }
}
