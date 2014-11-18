<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in('src')
;
return Symfony\CS\Config\Config::create()
    ->fixers(array('ordered_use', 'multiline_spaces_before_semicolon', 'concat_with_spaces'))
    ->finder($finder)
    ->setUsingCache(true)
    ;