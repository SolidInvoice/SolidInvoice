<?php

$file = "emoji.json";
$content = file_get_contents('emoji-list.json');

$emojis = explode("\n", $content);
$parsed = [];

foreach ($emojis as $emoji) {
    $icon = substr($emoji, 0, 4);
    $text = substr($emoji, 4);

    var_dump(json_encode($icon));

    $parsed[strtolower(trim($text))] = mb_convert_encoding($icon, 'UTF-8', 'HTML-ENTITIES');

    //$parsed[strtolower(trim($text))] = unicodeString(json_decode(json_encode($icon)));
}

//var_dump($parsed);

file_put_contents($file, json_encode($parsed, JSON_PRETTY_PRINT));


var_dump(unicodeString("\u{d83c}\u{df45}"));

function unicodeString($str, $encoding=null) {
    if (is_null($encoding)) $encoding = ini_get('mbstring.internal_encoding');
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function($match) use ($encoding) {
        return mb_convert_encoding(pack('H*', $match[1]), $encoding, 'UTF-16BE');
    }, $str);
}