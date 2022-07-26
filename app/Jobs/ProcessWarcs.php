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
use Mixnode\WarcReader;

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

        // Initialize a WarcReader object 
        // The WarcReader constructure accepts paths to both raw WARC files and GZipped WARC files
        $warc_reader = new WarcReader('/warc/' . $basename . $time . '.warc.gz');

        try {
            // Using nextRecord, iterate through the WARC file and output each record.
            while (($record = $warc_reader->nextRecord()) != FALSE) {

                if (!isset($record['header']['WARC-Target-URI'])) {
                    continue;
                }

                // Use your own regex here!
                preg_match_all('/[\'\"\s][a-z0-9._-]{0,35}@[a-z0-9._-]+\.[a-z]{2,4}/mi', $record['content'], $matches);

                foreach ($matches[0] as $match) {
                    $data[] = array($match, $record['header']['WARC-Target-URI']);
                }
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }



        // Or upload to S3
        // https://laravel.com/docs/9.x/filesystem#amazon-s3-compatible-filesystems
        Storage::disk('sftp')->put($basename . $time . '.txt', json_encode($data));
        unlink('/warc/' . $basename . $time . '.warc.gz');
    }
}
