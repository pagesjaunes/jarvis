<?php

class ForbiddenFunctionNames_Sniffs_Functions_ForbiddenFunctionsSniff extends Generic_Sniffs_PHP_ForbiddenFunctionsSniff
{
 /**
     * A cache of forbidden function names, for faster lookups.
     *
     * @var array(string)
     */
    protected $forbiddenFunctionNames = [
        'var_dump',
        'print_r',
        'dump',
        'debug',
        'var_export',
        'trigger_error',
        'header',
        'fastcgi_finish_request',
        'xdebug_debug_zval',
        'xdebug_debug_zval_stdout',
        'xdebug_var_dump',
        'xdebug_break',
        'set_error_handler',
        'set_exception_handler',
    ];
}
