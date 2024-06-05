<?php

namespace GemsTest\testUtils;

use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

trait ResultFetcherTrait
{
    protected ResultFetcher $resultFetcher;
    protected function getResultFetcher(): ResultFetcher
    {
        if (!isset($this->resultFetcher)) {
            $this->resultFetcher = new ResultFetcher($this->db);
        }
        return $this->resultFetcher;
    }
}