<?php

use Tests\DIServices\Test1;

return [
    "test" => fn($builder) => $builder->setClass(Test1::class),
];
