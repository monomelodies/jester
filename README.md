# Monomelodies/Jester
PHP7+ package for dynamically mocking objects

Jester offers a number of methods to dynamically mock objects, based on either:

- A parent class or instance;
- An abstract parent class;
- An interface;
- Using one of more specific traits.

Additionally, it allows you to, for any (mocked) object:

- Replace certain methods with a mock;
- "Prepend" certain or all methods with custom code;
- "Postfix" certain or all methods with custom code.

## Installation

Install using Composer:

```sh
cd /path/to/project
composer require monomelodies/jester

```

## Creating and accessing a Jester mock
Instantiate a Mock object with an optional class (instance) or interface name
as a parameter. If omitted, an anonymous class without any inheritance or
implementation will be used (generally not very useful, but who knows). To
access the generated instance, call the `getInstance` method:

```php
<?php

use Monomelodies\Jester\Mock;

$class = new Mock(stdClass);
var_dump($class->getInstance()); // class @ anonymous extend stdClass

```

Note that the generated object will always satisfy `instanceof`, `class_uses`
etc. calls.

If the mocked object takes constructor parameters, pass them in the optional
second argument as an array:

```php
<?php

class Foo
{
    public function __construct($foo)
    {
    }
}

$class = new Mock(Foo::class, [1]);

```

## Modifying the mocked object
Apart from `getInstance` a Mock object supplies a number of chainable methods
using which you may "decorate" your mocked object. Each of these returns the
decorated object so you can call them in succession until you're satisfied with
your mock, e.g.:

```php
<?php

$mock = (new Mock(stdClass))
    ->usesTrait(someTrait::class)
    ->replace('someMethod', function () {
        echo 'hello world!';
    })
    ->getInstance();

```

## Implementing interfaces
You can dynamically have the mock implement interfaces using the
`implementsInterface` method. It takes one or more interface names as its
arguments:

```php
<?php

$mock = $mock->implementsInterface(someInterface::class, someOtherInterface::class);

```

If the interface's contract isn't satisfied (i.e., one or more methods are
missing) Jester will mock them for you. If the methods exist but have the wrong
declaration, the mock will of course fail.

## Using traits
You can dynamically have the mock use trait using the `uses` method. Like for
interfaces it takes on or more trait names as arguments:

```php
<?php

$mock = $mock->uses(someTrait::class, someOtherTrait::class);

```

It is not possible to use traits in a "complex" way, e.g. with aliased methods.
If you need to do this, construct your own anonymous class before creating the
mock:

```php
<?php

$mock = new Mock(new class () extends Foo {
    use someTrait, someOtherTrait {
        someOtherTrait::bar insteadof someTrait::bar;
    };
});

```

## Replacing methods
You can replace (or add, if it doesn't otherwise exist) any method using the
`replace` method. Its first argument is the method name, its second a callable
containing the alternative implementation. Note that at runtime the callable is
bound to the mocked object, so `$this` works as expected.

```php
<?php

$mock = $mock->replaceMethod('fooBar', function () {
    return $this->barFoo();
});

```

> *CAUTION:* The mocked objects will _extend_ a base class, meaning private
> properties and methods will not work as expected. This is an acceptable side
> effect we think, since it will rarely make sence to mock something declared
> as "private".

