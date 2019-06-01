<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Factory;
use React\ChildProcess\Process;


class MainProcessController extends Controller
{
    private $global_mark;
    private $process_number = 1;
    private $child_process_key = 'child_process_';

    public function __construct()
    {
        parent::__construct();
        $this->global_mark = md5(microtime()) . '_';
    }

    public function handle()
    {
        Log::info($this->global_mark . 'main_process_act');
        $this->produceChildProcess();
        Log::info($this->global_mark . 'main_process_end');
    }

    public function produceChildProcess()

    {
        $loop = Factory::create();
        for ($i = 0; $i < $this->process_number; $i++) {
            $temp_child_process_key = $this->child_process_key . $i;
            $temp_cmd = "";
            $$temp_child_process_key = new Process($temp_cmd);
            $$temp_child_process_key->start($loop);
            $$temp_child_process_key->stdout->on('data', function ($chunk) use ($temp_child_process_key) {
                Log::info($temp_child_process_key . '_output', [$chunk]);
            });
        }
        $loop->run();
    }
}
