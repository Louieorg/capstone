<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Support\Facades\Validator;

$request = new StoreFeedbackRequest;
$rules = $request->rules();
$data = [
    'title' => 'Test report title',
    'category' => 'Facilities',
    'description' => 'This is a detailed description for reporting an issue that needs attention.',
    'impact' => 'This affects students and staff in many ways during daily operations.',
    'frequency' => 'Often',
    'current_process' => 'Manual or paper-based process',
    'affected_users' => '50-200',
    'affected_group' => ['Students'],
    'force_submit' => '1',
    'current_step' => 4,
];

$validator = Validator::make($data, $rules);
if ($validator->fails()) {
    echo "FAIL\n";
    print_r($validator->errors()->messages());
} else {
    echo "VALID\n";
}
