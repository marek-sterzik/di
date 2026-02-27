<?php

namespace Tests\DIServices;

class Test2
{
    public function __construct(private Test1 $test1)
    {
    }

    public function value(): string
    {
        return Test2::class;
    }

    public function subValue(): string
    {
        return $this->test1->value();
    }
}

