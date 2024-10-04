<?php

namespace Snadnee\Toolkit\Actions;

use Mockery;
use Mockery\Exception\RuntimeException;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use ReflectionException;
use Snadnee\Toolkit\Traits\Makeable;

abstract class Action
{
    use Makeable;

    /**
     * Mock the action in the container.
     *
     * @throws RuntimeException|ReflectionException
     */
    public static function mock(): MockInterface
    {
        $instance = Mockery::getContainer()->mock(static::class);

        app()->instance(static::class, $instance);

        return $instance;
    }

    /**
     * Initiate a mock expectation.
     */
    public static function shouldRun(): ExpectationInterface|Expectation
    {
        return static::mock()->shouldReceive('run');
    }
}
