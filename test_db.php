<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = App\Models\Set::whereHas('quiz', function($q) {
    $q->where('skill', 'reading')->where('part', 2);
})->withCount('questions')->get();

foreach($r as $s) {
    echo "Reading Part 2 Set {$s->id} has {$s->questions_count} Qs\n";
}

$l = App\Models\Set::whereHas('quiz', function($q) {
    $q->where('skill', 'listening')->where('part', 1);
})->withCount('questions')->get();

foreach($l as $s) {
    echo "Listening Part 1 Set {$s->id} has {$s->questions_count} Qs\n";
}
