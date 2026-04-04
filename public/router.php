<?php

// PHP built-in server router — passes all non-file requests to index.php
if (is_file(__DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
    return false; // serve static file directly
}

require_once __DIR__ . '/index.php';
