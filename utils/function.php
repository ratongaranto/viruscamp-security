<?php

function dumpThis($value){
    echo "<pre>";
        var_dump($value);
    echo "</pre>";
}

function dumpThisWithDie($value){
    dumpThis($value);
    die('Ici');
}