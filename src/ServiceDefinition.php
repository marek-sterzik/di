<?php

namespace Sterzik\DI;

use Sterzik\DI\Exception\NotImplementedException;

class ServiceDefinition
{
    private array $definitions = [];

    public function addDefinition(mixed $definition): self
    {
        $this->definitions[] = $definition;
        return $this;
    }

    public function applyToBuilder(ServiceBuilder $builder): self
    {
        foreach ($this->definitions as $serviceDefinition) {
            if (is_callable($serviceDefinition)) {
                $ret = $serviceDefinition($builder) ?? $builder;
                if ($ret !== $builder) {
                    $builder->setService($ret);
                }
            } else if(is_array($serviceDefinition)) {
                throw new NotImplementedException("Array service definitons are not yet supported.");
            } else {
                $builder->setService($serviceDefinition);
            }
        }
        return $this;
    }
}
