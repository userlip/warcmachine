<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\WarcReader;

class ProcessWarcs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $time = intval(microtime(true) * 1000);
        $data = array();
        // parsed path
        $path = parse_url($this->url, PHP_URL_PATH);
        // extracted basename
        $basename = basename($path);

        exec("aria2c -s16 -x16 -o ./../../../warc/{$basename}{$time}.warc.gz " . trim(str_replace('\r\n', '', $this->url)));

        $warc = new WarcReader();

        // Open example.warc.gz
        if (FALSE === $warc->open('/warc/' . $basename . $time . '.warc.gz')) {
            Log::alert('Error opening file');
            exit();
        }

        // Read records
        while (FALSE !== ($record = $warc->read())) {

            if (
                !isset($record['content'])
                || !isset($record['header']['WARC-Type'])
                || $record['header']['WARC-Type'] != "response"
            ) {
                continue;
            }

            // Use your own regex here!
            preg_match_all('/[\'\"\s][a-z0-9._-]{0,35}@[a-z0-9._-]+\.[a-z]{2,4}/mi', $record['content'], $matches);

            foreach ($matches[0] as $match) {
                $data[] = array($match, $record['header']['WARC-Target-URI']);
            }
        }

        uploadResults::dispatch('/warc/' . $basename . $time . '.txt', $data);

        // Close example.warc.gz
        if (FALSE === $warc->close()) {
            echo $warc->error() . PHP_EOL;
            exit();
        }

        unlink('/warc/' . $basename . $time . '.warc.gz');
    }
}
