<?php

    $content = explode('"', file_get_contents('/var/www/html/naumovich/config.json'));
    $config = array(
        'dayTime' => $content[3],
        'dayTemp' => $content[7],
        'nightTime' => $content[11],
        'nightTemp' => $content[15],
        'weekendTemp' => $content[19]        
    );
    
    print_r($config);
