<?php

namespace Tests\DIServices;

class TestValues
{
    private array $values = [];

    public function __construct(string $value)
    {
        $this->values[] = $value;
    }

    public function addValue(string $value): self
    {
        $this->values[] = $value;
        return $this;
    }

    public function addValueImplicit(string $value = "value"): self
    {
        return $this->addValue($value);
    }

    public function addValueNull(?string $value): self
    {
        $this->addValue(($value === null) ? "null" : "string");
        if ($value !== null) {
            $this->addValue($value);
        }
        return $this;
    }

    public function addValueVariadic(string $value, string ...$values): self
    {
        $this->addValue($value);
        foreach ($values as $value) {
            $this->addValue($value);
        }
        return $this;
    }

    public function addAllValues(): self
    {
        foreach (func_get_args() as $arg) {
            $this->addValue($arg);
        }
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getValuesString(): string
    {
        return implode(",", $this->getValues());
    }

}
