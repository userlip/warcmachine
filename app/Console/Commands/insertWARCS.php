<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWarcs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class insertWARCS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insertWARCS {warc} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $limit = 0;

        $warc_paths = file_get_contents("https://data.commoncrawl.org/crawl-data/" . $this->argument('warc') . "/warc.paths.gz");
        file_put_contents('/tmp/' . $this->argument('warc') . '.gz', $warc_paths);

        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time
        $out_file_name = str_replace('.gz', '', '/tmp/' . $this->argument('warc') . '.gz');

        // Open our files (in binary mode)
        $file = gzopen('/tmp/' . $this->argument('warc') . '.gz', 'rb');
        $out_file = fopen($out_file_name, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        print_r(file_get_contents($out_file_name));

        $handle = fopen($out_file_name, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                // process the line read.

                if ($limit == $this->argument('limit')) {
                    break;
                }

                ProcessWarcs::dispatch('https://data.commoncrawl.org/' . $line)->onQueue('warcs');

                $limit++;
            }

            fclose($handle);
        }

        unlink($out_file_name);
        unlink('/tmp/' . $this->argument('warc') . '.gz');
    }
}
