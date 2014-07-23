<?php
/*
 MIT License
Copyright (c) 2014 Andy Hassall

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/

namespace Pheal\RateLimiter;

use Pheal\Exceptions\PhealException;

/**
 * Simple "leaky bucket" rate limiter to avoid exceeding the CCP-defined
 * API rate limits.
 *
 * https://wiki.eveonline.com/en/wiki/EVE_API
 * Rate Limit: 30 requests per second
 *
 * Uses advisory file locking for exclusive access to lock file.
 */
class FileLockRateLimiter implements CanRateLimit
{
    /**
     * lock file apth
     * @var string
     */
    protected $lockFilePath;

    /**
     * Maximum requests per second.
     * @var int
     */
    protected $requestsPerSecond;

    /**
     * Maximum initial burst of requests before throttling is applied.
     * @var int
     */
    protected $maxBurst;

    /**
     * Maximum time in seconds to wait for rate limiter to allow call.
     * @var int
     */
    protected $maxWait;

    public function __construct($basePath, $requestsPerSecond = 30, $maxBurst = 10, $maxWait = 10)
    {
        if (!$basePath) {
            $basePath = sys_get_temp_dir();
        }

        $this->lockFilePath = join(DIRECTORY_SEPARATOR, [$basePath, 'pheal_ratelimiter.lock']);

        $this->requestsPerSecond = $requestsPerSecond;
        $this->maxBurst = $maxBurst;
        $this->maxWait = $maxWait;
    }

    public function rateLimit()
    {
        $now = time();

        do {
            if ($this->canProceed()) {
                return true;
            }

            // Random sleep before trying again.
            usleep(mt_rand(500, 5000));

        } while (time() - $now < $this->maxWait);

        throw new PhealException("Timed out waiting for rate limiter, waited " . (time() - $now) . " seconds");
    }

    protected function canProceed()
    {
        // Open file, create if does not exist
        $fp = fopen($this->lockFilePath, "a+");

        if (!$fp) {
            throw new PhealException("Cannot open rate limiter lock file " . $this->lockFilePath . ': ' . error_get_last());
        }

        if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock

            fseek($fp, 0);
            $bucketSize = trim(fgets($fp));
            $lastRequest = trim(fgets($fp));

            $now = microtime(true);

            // Empty out slots based on time since last request
            $bucketsToFree = floor(($now - $lastRequest) * $this->requestsPerSecond);
            $bucketSize = max(0, $bucketSize - $bucketsToFree);

            if ($bucketSize < $this->maxBurst) {
                $bucketSize++;
                $lastRequest = microtime(true);

                ftruncate($fp, 0);      // truncate file
                fwrite($fp, $bucketSize . "\n");
                fputs($fp, $lastRequest . "\n");
                fflush($fp);            // flush output before releasing the lock
                flock($fp, LOCK_UN);    // release the lock
                fclose($fp);

                return true;
            } else {
                fclose($fp);

                return false;
            }
        } else {
            fclose($fp);

            return false;
        }
    }
}
