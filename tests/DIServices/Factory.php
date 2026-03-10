<?php

namespace Tests\DIServices;

class Factory
{
    public function createTest1(): Test1
    {
        return new Test1();
    }
}
