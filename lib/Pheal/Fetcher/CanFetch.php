<?php
namespace Pheal\Fetcher;

interface CanFetch
{
    /**
     * @param string $url
     *
     * @return string
     */
    public function fetch($url, $opts);
}
