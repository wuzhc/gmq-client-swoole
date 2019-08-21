<?php

spl_autoload_register(function ($className) {
    if (strpos($className, '\\') !== false) {
        $classFile = dirname(__FILE__) . '/' . str_replace('\\', '/', $className) . '.php';
        if (is_file($classFile)) {
            include($classFile);
        } else {
            echo sprintf("%s is not found\n", $classFile);
            exit(-1);
        }
    }
});