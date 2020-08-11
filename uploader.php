<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/*  uploader  */

require "./ChunkUploadHandler.php";

define('UPATH', DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR);

$up = new \apimediator\ChunkUploadHandler(UPATH);

$up->onRequest();

