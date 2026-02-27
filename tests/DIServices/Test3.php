<?php

namespace Tests\DIServices;

class Test3
{
    public function __construct(private Test1 $test1, private Test2 $test2, private string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function subValues(): array
    {
        return [$this->test1->value(), $this->test2->value()];
    }
}
