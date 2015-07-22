<?php

namespace Pheal\Tests;

use Pheal\Pheal;
use PHPUnit_Framework_TestCase;

/**
 * @author Kevin Mauel
 */
class PhealTest extends PHPUnit_Framework_TestCase {

    public function testBasicPhealUsage()
    {
        $pheal = new Pheal();
        $response = $pheal->serverScope->ServerStatus();

        $this->assertTrue(
            $response->serverOpen === 'True' ||
            $response->serverOpen === 'False'
        );
    }
}
