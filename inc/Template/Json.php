<?php

abstract class Template_Json
{
    public static function render(array $data) {
        return json_encode($data);
    }

}