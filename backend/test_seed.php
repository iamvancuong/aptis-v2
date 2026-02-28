<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    $kernel->call('db:seed', ['--class' => 'SpeakingSkillSeeder']);
    file_put_contents('test_seed_output.log', "Success: " . $kernel->output());
} catch (\Throwable $e) {
    file_put_contents('test_seed_output.log', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
