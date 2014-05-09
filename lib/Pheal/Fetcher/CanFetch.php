<?php
namespace Pheal\Fetcher;

interface CanFetch
{
    /**
     * @param string $url
     * @param array $opts
     * @return string
     */
    public function fetch($url, $opts);
}
