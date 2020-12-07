<?php

    header('Content-Type: text/plain');
    
    if (isset($_REQUEST['config'])) {
        $params = explode('|', $_REQUEST['config']);
        
        if (count($params) == 5) {
            $new_config = '{
                "dayTime": "'.$params[0].'",
                "dayTemp": "'.$params[1].'",
                "nightTime": "'.$params[2].'",
                "nightTemp": "'.$params[3].'",
                "weekendTemp": "'.$params[4].'"
}';
            
            file_put_contents('/var/www/html/naumovich/config.json', $new_config);
            
            echo 'New config is setted';
            exit;
        }
        echo 'Wrong config!';
        exit;
    }
    
    echo 'No config!';
    