<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class uploadResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $filename)
    {
        $this->data = $data;
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // SFTP UPLOAD
        // Or upload to S3
        // https://laravel.com/docs/9.x/filesystem#amazon-s3-compatible-filesystems
        Storage::disk('sftp')->put($this->filename, json_encode($this->data));
    }
}
