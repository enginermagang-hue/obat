<?php

namespace Tests\Feature;

use App\Console\Commands\AiTrainModels;
use Tests\TestCase;

class AiTrainModelsCommandTest extends TestCase
{
    public function test_command_has_correct_configuration(): void
    {
        $command = new AiTrainModels;

        $this->assertSame('ai:train-models', $command->getName());
        $this->assertSame('Train AI prediction models for all facility+drug combinations', $command->getDescription());
    }

    public function test_command_has_expected_options(): void
    {
        $command = new AiTrainModels;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('fasilitas-id'));
        $this->assertTrue($definition->hasOption('obat-id'));
    }
}
