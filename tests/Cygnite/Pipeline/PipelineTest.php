<?php

use Cygnite\Foundation\Application;
use Cygnite\Pipeline\Pipeline;

class PipelineTest extends PHPUnit_Framework_TestCase
{
    private $app;
    private $pipeline;

    /**
     * Sets up the tests.
     */
    public function setUp()
    {
        $this->app = Application::instance();
        $this->pipeline = new Pipeline($this->app);
    }

    public function testBasicUsage()
    {
        $pipes = [
            function ($request, $next) {
                return $next($request + 1); // 2+1= 3
            },
        ];

        $this->pipeline->send(2)
             ->through($pipes)
             ->then(function ($request) {
                 return $request + 4; // 3+ 4 = 7
             });

        $this->assertEquals(7, $this->pipeline->run());
    }

    public function testGetMethod()
    {
        $pipes = [
            function ($request, $next) {
                return $next($request.'Pipe');
            },
        ];
        $this->pipeline->send('request')
            ->through($pipes, 'filter');

        $this->assertSame('filter', $this->pipeline->getMethod());
    }

    public function testMultiplePipelineCalls()
    {
        $pipes = [
            function ($request, $next) {
                return $next($request + 1); // 2+1= 3
            },
            function ($request, $next) {
                return $next($request + 3); // 3+3 =6
            },
        ];

        $this->pipeline->send(2)
            ->through($pipes)
            ->then(function ($request) {
                return $request + 4; // 6+ 4 = 10
            });

        $this->assertEquals(10, $this->pipeline->run());
    }

    public function testSingleMiddlewareObjectCallsDefaultMethod()
    {
        $pipes = [new PipelineTestA()];

        $this->pipeline->send('Welcome')
             ->through($pipes);

        $this->assertEquals(trim('Welcome PipelineTestA'), trim($this->pipeline->run()));
    }

    public function testSingleMiddlewareObjectCallsGivenMethod()
    {
        $pipes = [new PipelineTestB()];

        $this->pipeline->send('Hello')
            ->through($pipes, 'process');

        $this->assertEquals(trim('Hello Process'), trim($this->pipeline->run()));
    }

    public function testMultipleMiddlewareObject()
    {
        $pipes = [new PipelineTestA(), new PipelineTestB()];

        $this->pipeline->send('Hello')
             ->through($pipes);

        $this->assertEquals(trim('Hello PipelineTestA_PipelineTestB'), trim($this->pipeline->run()));
    }

    public function testThenClosureWithObjectPipes()
    {
        $pipes = [
            function ($request, $next) {
                return $next($request.'3');
            },
            new PipelineTestA(),
        ];
        $this->pipeline->send('input')
            ->through($pipes);
        $this->assertEquals('input3 PipelineTestA', $this->pipeline->run());
    }

    public function testResolvingPipesThroughContainer()
    {
        $app = $this->setValueToApplication();
        $this->pipeline->setContainer($app);

        $this->pipeline->send('Foo')
            ->through(['pipeline.request'], 'run');

        $this->assertSame('Foo Bar', $this->pipeline->run());
    }

    public function testResolvingPipesThroughContainerWithParameter()
    {
        $app = $this->setValueToApplication();
        $this->pipeline->setContainer($app);

        $parameters = ['first', 'second'];

        $this->pipeline->send('Foo')
            ->through(['PipelineRequest:'.implode(',', $parameters)], 'process');

        $this->assertSame('Foo-first-second', $this->pipeline->run());
    }

    public function testResolvingMultiplePipesThroughContainerWithParameter()
    {
        $app = $this->setValueToApplication('pipeline.resolver');
        $this->pipeline->setContainer($app);

        $parameters = ['third', 'fourth'];

        $this->pipeline->send('Foo')
            ->through(['PipelineRequest:'.implode(',', $parameters), 'pipeline.resolver'], 'process');

        $this->assertEquals('Foo-third-fourth-Bar', $this->pipeline->run());
        $this->assertSame('Foo-third-fourth-Bar', $this->pipeline->run());
    }

    private function setValueToApplication($key = 'pipeline.request')
    {
        if ($key == 'pipeline.resolver') {
            $this->app[$key] = new PipelineResponse();
        } else {
            $this->app[$key] = new PipelineRequest();
        }

        return $this->app;
    }
}

class PipelineTestA
{
    public function handle($request, $next)
    {
        $request .= ' PipelineTestA';

        return $next($request);
    }
}

class PipelineTestB
{
    public function handle($request, $next)
    {
        $request .= '_PipelineTestB';

        return $next($request);
    }

    public function process($request, $next)
    {
        $request .= ' Process';

        return $next($request);
    }
}

class PipelineRequest
{
    public function run($request, $next)
    {
        $request .= ' Bar';

        return $next($request);
    }

    public function process($request, $next, $first, $second)
    {
        $request .= '-'.$first.'-'.$second;

        return $next($request);
    }
}

class PipelineResponse
{
    public function process($request, $next)
    {
        $request .= '-Bar';

        return $next($request);
    }
}
