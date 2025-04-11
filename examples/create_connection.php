<?php

require __DIR__ . '/../vendor/autoload.php';

use QubeSync\QubeSync;

# make sure to set your QUBE_API_KEY in your environment variables

$connectionId = QubeSync::createConnection(function ($connectionId) {
    echo "Connection created with ID: $connectionId\n";
});

$response = QubeSync::get("connections/$connectionId");
print_r($response);
