<?php

/**
 * General helper functions
 * @author &copy; Niko Hujanen, 2011
 * @version ALPHA
 */

function ifset(&$value, $default = null)
{
    if (isset($value) && !empty($value)) return $value;
    return $default;
}