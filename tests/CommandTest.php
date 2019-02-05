<?php

namespace Ben182\AbTesting\Tests;

use Ben182\AbTesting\Models\Goal;
use Ben182\AbTesting\AbTestingFacade;
use Ben182\AbTesting\Models\Experiment;

class CommandTest extends TestCase
{
    public function test_flush_command()
    {
        $this->assertCount(0, Experiment::all());
        $this->assertCount(0, Goal::all());

        AbTestingFacade::pageview();

        $this->assertCount(2, Experiment::all());
        $this->assertCount(4, Goal::all());

        $this->artisan('ab:flush');

        $this->assertCount(0, Experiment::all());
        $this->assertCount(0, Goal::all());
    }
}