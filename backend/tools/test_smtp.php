<?php
// Simple SMTP connectivity tester
$host = 'smtp.hostinger.com';
$port = 465;
$timeout = 5;

echo "Testing plain fsockopen to $host:$port\n";
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    echo "fsockopen: connected to $host:$port\n";
    fclose($fp);
} else {
    echo "fsockopen: connection failed: $errstr ($errno)\n";
}

echo "\nTesting ssl:// stream_socket_client to $host:$port\n";
$target = sprintf('ssl://%s:%d', $host, $port);
$errNo = 0;
$errStr = '';
$fp2 = @stream_socket_client($target, $errNo, $errStr, $timeout);
if ($fp2) {
    echo "stream_socket_client: connected to $target\n";
    fclose($fp2);
} else {
    echo "stream_socket_client: connection failed: $errStr ($errNo)\n";
}

// Optionally test STARTTLS (connect to 587 then request starttls) - we just test TCP connect here
$port2 = 587;
echo "\nTesting plain TCP connect to $host:$port2 (STARTTLS)\n";
$fp3 = @fsockopen($host, $port2, $errno3, $errstr3, $timeout);
if ($fp3) {
    echo "fsockopen: connected to $host:$port2\n";
    fclose($fp3);
} else {
    echo "fsockopen: connection to $host:$port2 failed: $errstr3 ($errno3)\n";
}
