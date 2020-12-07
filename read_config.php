<?php


    if (isset($argv[1])) {// check if argument for given temp added
        echo "New given temp value found!\n";
        $min_temp = $argv[1];
    } else {
        // read config from config.json
        $content = explode('"', file_get_contents('/var/www/html/naumovich/config.json'));
        
        if (count($content) > 0) {
            $config = array(
                'dayTime' => $content[3],
                'dayTemp' => $content[7],
                'nightTime' => $content[11],
                'nightTemp' => $content[15],
                'weekendTemp' => $content[19]        
            ); 
            
            echo 'Config ';
            print_r($config);
            
            $day_of_week = date('w');
            $current_hour = date('G');
            
            if ($day_of_week == 0 OR $day_of_week == 6){ // today is weekend
                echo "It's the weekend now!\n";
                $min_temp = $config['weekendTemp'];
            } else {
                if ($current_hour < $config['nightTime'] 
                        AND $current_hour >= $config['dayTime']) { // day time
                    echo "It's a day time now!\n";
                    $min_temp = $config['dayTemp'];
                } else { 
                    // night time
                    echo "It's a night time now!\n";
                    $min_temp = $config['nightTemp'];
                }
            };
        } else {
            echo "No given temp in parameters!\n";
            $min_temp = MIN_TEMP;
        }    
    }
