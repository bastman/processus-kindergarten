<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/23/12
 * Time: 4:15 PM
 * To change this template use File | Settings | File Templates.
 */

namespace HelloworldExample;

require __DIR__ . '/../../../vendor/autoload.php';

use Processus\Kindergarten\Playground;
use Exception;
use HelloworldExample\Bootstrap;

Bootstrap::getInstance()
    ->init();

// do stuff ...
$data = array();
try {
    $value = $data[0]; // E_NOTICE to Exception
} catch (Exception $e) {
    var_dump('EXCEPTION CATCHED.');
}

// use dirty legacy code: but sandboxed.
var_dump('using dirty code, sandboxed ... ');
$playground = Bootstrap::getInstance()
    ->getPlayground();
$sandbox = $playground->createSimpleSandbox();

$sandbox
    ->setErrorReportingCaptureLevel(
    $sandbox->getErrorReportingCaptureLevelAllStrictNoNotice()
)
    ->setDelegateExceptionEnabled(false) // catch exceptions
    ->setToy(
    function () {

        $data = array();
        $value = $data[0]; // E_NOTICE, but not captured at errorReporting
        var_dump('the value is ...');
        var_dump($value);
        var_dump('this was dirty');

        return true;
    }
)
    ->setOnError(
    function (Playground $playground, $error) {
        var_dump('ERROR');
    }
)
    ->setParams(array())
    ->play();
var_dump('--------- SANDBOX HAS EXCEPTION -----------');
var_dump($sandbox->hasException());
if ($sandbox->hasException()) {
    var_dump($sandbox->getException()->getMessage());
}
var_dump('--------- SANDBOX RESULT -----------');
var_dump($sandbox->getResult());

// do stuff, all sandbox settings have been removed ...

var_dump('doing dirty stuff, unsandboxed ...');
$data = array();
try {
    $value = $data[0]; // E_NOTICE to Exception
} catch (Exception $e) {
    var_dump('EXCEPTION CATCHED.');
}