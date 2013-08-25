<?php
namespace Pheal\Fetcher;

interface CanFetch
{
    public function fetch($url, $opts);
}
