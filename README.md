Simple Bootstrapping and Sandboxing of untrusted legacy code

Features:

- decorate set_error_handler & errorreporting: in sync ! switch'em whenever you want during runtime.
- decorate set_exception_handler
- decorate register_shutdown_function: unfortunately all functions get executed. Solution: Callback Proxy and Manager
- callback functions for all kind of events (onError, onException, onShutdownFailed, ...). No need to override class methods when trying to tweak current business logic.
- sandbox untrusted legacy code: all error-/exception-/shutdown-settings you changed to make dirty code run will be reverted after executing.


Sandbox Example: let's play with dirty legacy toy ...

    // use dirty legacy code: but sandboxed.
    var_dump('using dirty code, sandboxed ... ');
    $playground = Bootstrap::getInstance()
      ->getPlayground();
    $sandbox = $playground->createSimpleSandbox();

    $sandbox
      ->setErrorReportingCaptureLevel(
        $sandbox->getErrorReportingCaptureLevelAllStrictNoNotice() // (E_ALL|E_STRICT) ^ E_NOTICE
      )
      ->setDelegateExceptionEnabled(false) // catch exceptions, do not rethrow
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