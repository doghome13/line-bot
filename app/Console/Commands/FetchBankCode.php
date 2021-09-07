<?php

namespace App\Console\Commands;

use DB;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Console\Command;

class FetchBankCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '撈取銀行代碼';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // 銀行
        $this->sources = 'https://www.findinfo.info/';

        // 農會
        $this->sourcesOfPeasant = 'https://www.findinfo.info/farmcode.html';

        $this->banks     = [];
        $this->findCodes = []; // 已記錄的銀行代碼
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->checkUrl($this->sources)) {
            $this->error($this->sources.' not reachable!');

            return;
        }

        if (! $this->checkUrl($this->sourcesOfPeasant)) {
            $this->error($this->sourcesOfPeasant.' not reachable!');

            return;
        }

        try {
            // 銀行
            $this->fetchBankBy($this->sources);

            // 農會
            $this->fetchBankBy($this->sourcesOfPeasant);

            if (empty($this->banks)) {
                $this->line('sources is empty.');

                return;
            }

            // 資料輸出、後續處理...
            dump($this->banks);
            $this->line('done!');

            // DB::beginTransaction();
            // DB::commit();
        } catch (Exception $th) {
            // DB::rollback();
            $this->error('LINE: '.$th->getLine());
            $this->error($th->getMessage());
        }
    }

    /**
     * 驗證來源.
     *
     * @return bool
     */
    private function checkUrl($url)
    {
        $headers = @get_headers($url);

        return is_array($headers) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $headers[0]) : false;
    }

    /**
     * 撈取銀行代碼和名稱.
     *
     * @return void
     */
    private function fetchBankBy($url)
    {
        $dom               = new DomDocument;
        $dom->formatOutput = true;
        libxml_use_internal_errors(true);
        $dom->loadHTML(file_get_contents($url));
        libxml_clear_errors();

        $domxpath  = new DOMXPath($dom);
        $total     = $domxpath->query('//main/div');

        // 銀行
        foreach ($total as $element) {
            $findCode = $element->getElementsByTagName('div')->item(0) ?? null;
            $findName = $element->getElementsByTagName('div')->item(1) ?? null;

            if ($findCode !== null
                && is_numeric($findCode->nodeValue)
                && ! in_array($findCode->nodeValue, $this->findCodes)) {
                $code              = strlen($findCode->nodeValue) <= 3
                    ? $findCode->nodeValue
                    : substr_replace($findCode->nodeValue, ',', 3, 0);
                $this->banks[]     = ['code' => $code, 'name' => $findName->nodeValue];
                $this->findCodes[] = $findCode->nodeValue;
            }
        }
    }
}
