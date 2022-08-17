<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    /**
     * Test command with test file.
     *
     * @return void
     */
    public function test_command_with_test_file()
    {
        // Set rates as in the brief doc
        Config::set('wallet.currencies.USD.rate', 1.1497);
        Config::set('wallet.currencies.JPY.rate', 129.53);

        // Call the command without mocking console output to be able to assert the full output.
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('commission-fees:calculate', [
            'filename' => 'test-file'
        ]);

        $output = Artisan::output();

        $this->assertEquals("0.60\n3.00\n0.00\n0.06\n1.50\n0\n0.70\n0.30\n0.30\n3.00\n0.00\n0.00\n8612\n", $output);
        $this->assertEquals(0, $exitCode);

    }
}
