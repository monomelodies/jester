<?php

namespace Monomelodies\Jester;

use ReflectionMethod;

class MethodBuilder
{
    private $reflection;

    public function __construct(ReflectionMethod $reflection)
    {
        $this->reflection = $reflection;
    }

    public function getSignature()
    {
        $accessibility = 'public';
        if ($this->reflection->isPrivate()) {
            $accessibility = 'private';
        } elseif ($this->reflection->isProtected()) {
            $accessibility = 'protected';
        }
        $static = $this->reflection->isStatic() ? ' static' : '';
        $name = $this->reflection->name;
        $arguments = [];
        foreach ($this->reflection->getParameters() as $parameter) {
            $argument = '$'.$parameter->getName();
            if ($parameter->isPassedByReference()) {
                $argument = "&$argument";
            }
            if ($parameter->isVariadic()) {
                $argument = "...$argument";
            }
            if ($argtype = $parameter->getType()) {
                $argument = "$argtype $argument";
            }
            if ($parameter->isDefaultValueAvailable()) {
                $argument .= ' = '.$this->tostring($parameter->getDefaultValue());
            }
            $arguments[] = $argument;
        }
        $arguments = implode(', ', $arguments);
        $returnType = '';
        if ($this->reflection->hasReturnType()) {
            $type = $this->reflection->getReturnType();
            $returnType = " : $type";
        }
        return "$accessibility$static function $name($arguments)$returnType";
    }

    public function getArgumentList()
    {
        $arguments = [];
        foreach ($this->reflection->getParameters() as $parameter) {
            $arguments[] = '$'.$parameter->getName();
        }
        return $arguments;
    }

    /**
     * Internal helper method to get an echo'able representation of a random
     * value for reporting and code generation.
     *
     * @param mixed $value
     * @return string
     */
    private function tostring($value)
    {
        if (!isset($value)) {
            return 'NULL';
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (is_string($value)) {
            return "'$value'";
        }
        if (is_array($value)) {
            $out = '[';
            $i = 0;
            foreach ($value as $key => $entry) {
                if ($i) {
                    $out .= ', ';
                }
                $out .= $key.' => '.$this->tostring($entry);
                $i++;
            }
            $out .= ']';
            return $out;
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return "$value";
            } else {
                return get_class($value);
            }
        }
    }
}

