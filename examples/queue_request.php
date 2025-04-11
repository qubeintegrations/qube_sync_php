<?php

require __DIR__ . '/../vendor/autoload.php';

use QubeSync\QubeSync;

# make sure to set your QUBE_API_KEY in your environment variables

$connectionId = QubeSync::createConnection(function ($connectionId) {
    echo "Connection created with ID: $connectionId\n";
});

$requestXML = <<<XML
<?xml version="1.0"?>
<?qbxml version="16.0"?>
<QBXML>
  <QBXMLMsgsRq onError="stopOnError">
    <CustomerQueryRq requestID="1">
      <MaxReturned>10</MaxReturned>
    </CustomerQueryRq>
  </QBXMLMsgsRq>
</QBXML>
XML;

# create a queued request
$response = QubeSync::queueRequest($connectionId, [
    'request_xml' => $requestXML,
    # 'webhook_url' => 'https://example.com/webhook',
]); 

print_r($response);