<?php

$output = shell_exec("/opt/livigent/crt/opt/livigent/sbin/rpc_client reload_component '\"livigent-dispatcher\"'");

if (is_null($output)) {
    echo 'no output';
} else {
    echo $output;
}