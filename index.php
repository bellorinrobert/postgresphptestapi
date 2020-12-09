<?php

require_once 'JWTokenGenerator.php';

$generator = new JWTokenGenerator();

echo $generator->generate();

