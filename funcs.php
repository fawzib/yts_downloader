<?php

class Funcs {

    public static function safe_filename($filename) {
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
        $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
        return $filename;
    }
}
