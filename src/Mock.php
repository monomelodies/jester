<?php

namespace Monomelodies\Jester;

use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use LogicException;

class Mock
{
    private $className;
    private $constructionArguments = [];
    private $implements = [];
    private $uses = [];
    private $methods = [];
    private $reflection;

    /**
     * Constructor. Pass in an optional base object or classname (defaults to an
     * anonymous class) and optional array of constructor arguments.
     *
     * @param mixed $base A class, interface or trait name, an instance of a
     *  class or null to use an anonymous class.
     */
    public function __construct(string $base = null, array $ctor = [])
    {
        if (!isset($base)) {
            $base = 'class';
            $this->reflection = new ReflectionClass(new class () {});
        } else {
            $this->reflection = new ReflectionClass($base);
        }
        $this->className = $base;
        $this->constructionArguments = $ctor;
    }

    /**
     * Add one or more interfaces to implement.
     *
     * @param ...$interfaces Name(s) of the interface(s).
     * @return Monomelodies\Jester\Mock
     */
    public function implementsInterface(...$interfaces)
    {
        $this->implements = array_unique(array_merge($this->implements, $interfaces));
        return $this;
    }

    /**
     * Add one or more traits to use.
     *
     * @param ...$traits Name(s) of the trait(s) to use.
     * @return Monomelodies\Jester\Mock
     */
    public function uses(...$traits)
    {
        $this->uses = array_unique(array_merge($this->uses, $traits));
        return $this;
    }

    /**
     * Replace (or add if it doesn't exist) a method with a callable. Note that
     * the callable is bound to the mocked object, so `$this` will work as
     * expected.
     *
     * @param string $method The name of the method to replace.
     * @param callable $callable The callable to use instead.
     * @return Monomelodies\Jester\Mock
     */
    public function replace(string $method, callable $callable)
    {
        $this->methods[$method] = $callable;
        return $this;
    }

    public static function getMethod($object, $name)
    {
        return $object->methods[$name];
    }

    /**
     * Returns the fully mocked instance.
     *
     * @return object
     */
    public function getInstance()
    {
        $code = "return new class";
        if ($this->constructionArguments) {
            $code .= "(...\$this->constructionArguments)";
        } else {
            $code .= "()";
        }
        if ($this->className != 'class') {
            $code .= " extends {$this->className}";
        }
        if ($this->implements) {
            $code .= " implements ".implode(', ', $this->implements);
        }
        $code .= " {\n";
        if ($this->uses) {
            $code .= "use ".implode(', ', $this->uses).";\n";
        }
        $tmp = eval("$code};");
        foreach ($this->methods as $name => $definition) {
            $reflection = new ReflectionMethod($tmp, $name);
            if ($reflection->isFinal()) {
                throw new LogicException("Cannot override final method $name");
            }
            $method = new MethodBuilder($reflection);
            $def = $method->getSignature();
            $arguments = implode(', ', $method->getArgumentList());
            if ($arguments) {
                $arguments = ", $arguments";
            }
            $code .= "$def {
    return \Monomelodies\Jester\Mock::getMethod(\$this->__Jester, '$name')
        ->call(\$this$arguments);
}\n";
        }
        $code .= " };";
        $instance = eval($code);
        $instance->__Jester = $this;
        return $instance;
    }
}

