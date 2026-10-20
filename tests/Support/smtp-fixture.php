<?php

// One disposable IPv6 loopback SMTP connection; never contact a remote host.
$server = stream_socket_server('tcp://[::1]:0', $code, $error);
if ($server === false) exit(1);
echo stream_socket_get_name($server, false) . "\n";
flush();
$client = stream_socket_accept($server, 15);
if ($client === false) exit(2);
stream_set_timeout($client, 10);
fwrite($client, "220 fixture ESMTP\r\n");
$message = '';
$data = false;
while (($line = fgets($client)) !== false) {
    if ($data) {
        if ($line === ".\r\n") {
            file_put_contents($argv[1], $message);
            fwrite($client, "250 queued\r\n");
            $data = false;
        } else {
            $message .= $line;
        }
        continue;
    }
    $command = strtoupper(strtok($line, " \r\n"));
    if ($command === 'QUIT') { fwrite($client, "221 bye\r\n"); break; }
    if ($command === 'DATA') { fwrite($client, "354 send message\r\n"); $data = true; }
    else fwrite($client, "250 fixture\r\n");
}
fclose($client);
fclose($server);
exit($message === '' ? 3 : 0);
