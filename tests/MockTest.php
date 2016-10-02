<?php

namespace Monomelodies\Tests;

use Monomelodies\Jester\Mock;

require_once __DIR__.'/../demo/classes.php';

class MockTest
{
    /**
     * At its most basic, we can mock an object based on another object {?} and
     * have it implement an interface {?} and use a trait {?}.
     */
    public function testMock(Mock $mock)
    {
        $mock->__gentryConstruct(Foo::class);
        $mock->implementsInterface(testInterface::class)
            ->uses(testTrait::class);
        $instance = $mock->getInstance();
        yield assert($instance instanceof Foo);
        yield assert($instance instanceof testInterface);
        yield assert(in_array(testTrait::class, class_uses($instance)));
    }

    /**
     * We can replace methods and call them as expected {?}.
     */
    public function testReplacing(Mock $mock)
    {
        $mock->__gentryConstruct(Foo::class);
        $mock->replace('sayHi', function () {
            $this->foo = 'bar';
            return parent::sayHi();
        });
        yield assert($mock->getInstance()->sayHi() == 'bar');
    }
}

