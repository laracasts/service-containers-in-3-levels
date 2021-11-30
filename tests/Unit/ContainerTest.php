<?php

namespace Tests\Unit;

use App\Container;
use App\Http;
use App\Mailchimp;
use App\Newsletter;
use Tests\TestCase;

class ContainerTest extends TestCase
{
    /** @test */
    function LEVEL_ONE_its_like_a_toy_chest()
    {
        $container = new Container();

        $container->bind('foo', 'bar');

        $this->assertEquals('bar', $container->get('foo'));
    }

    /** @test */
    function LEVEL_TWO_it_can_resolve_functions_and_singletons()
    {
        $container = new Container();

        $container->bind('newsletter', function () {
            return new Newsletter(
                new Mailchimp(new Http())
            );
        });

        $this->assertInstanceOf(Newsletter::class, $container->get('newsletter'));

        $container->singleton('newsletter', function () {
            return new Newsletter(
                new Mailchimp(new Http())
            );
        });

        $newsletter1 = $container->get('newsletter');
        $newsletter2 = $container->get('newsletter');

        $this->assertSame($newsletter1, $newsletter2);
    }

    /** @test */
    function LEVEL_THREE_it_can_do_magic()
    {
        $container = new Container();

        $this->assertInstanceOf(Newsletter::class, $container->get(Newsletter::class));
    }
}
