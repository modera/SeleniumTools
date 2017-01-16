<?php

// File autoload.php is automatically created by composer when running composer update/install commands.
// Feel free to change the path
require __DIR__.'/../vendor/autoload.php';

use Modera\Component\SeleniumTools\VideoRecording\FFMPEGServer\Server;

$server = new Server();
Server::sendResponse($server->handleRequest($_SERVER));