<?php

/*
 * Naumovich 3.0 App can control korovnik heating system state and mode.
 * Run this script every 20 minutes by crontab at korovnik server 
 * and heating system will work with remote algorithm instead of built-in. 
 */

date_default_timezone_set('Europe/Kiev');

// index constants for state array
define("MAXSIZE", 100);
define("MODE", 2);
define("OUTDOOR", 8);
define("RUSLAN",  9);
define("FASMEB", 10);
define("BOILER", 11);
define("BACK", 12);

define("DELTA_FOR_ACTIVE_MODE", 0.3);

define("MIN_TEMP", 5);
define("WINTER_TEMP", -5);

// MODE constants for settng working mode
define("OFF", 0);
define("ECO", 1);
define("STANDART", 2);

$heater_modes = array(
    OFF => "OFF",
    ECO => "ECO",
    STANDART => "STANDART"
);
/*
define("URL",'http://10.8.0.66/naumovich/command.php?command=');
define('LOG_FILE_PATH','/home/user/naumovich3.log');
define('MAX_LOG_SIZE', 240);
 */

define("URL",'http://127.0.0.1/naumovich/command.php?command=');
define('LOG_FILE_PATH','/var/log/naumovich3.log');
define('STATE_LOG_FILE_PATH','/var/log/naumovich3_states.log');
define('MAX_LOG_SIZE', 17000);


$min_temp = (isset($argv[1])) ? $argv[1] : MIN_TEMP;


echo "Naumovich 3.0 heating system for korovnik\n";
echo "======================================\n";

$state = get_answer_for_AT_commant('AT get all');

save_state_log($state);

echo "====== Current heater  state is:======\n";
echo "Outdoor:",$state[OUTDOOR],"C\n";
echo "Ruslan: ",$state[RUSLAN],"C\n";
echo "Fasmeb: ",$state[FASMEB],"C\n";
echo "Boiler: ",$state[BOILER],"C\n";
echo "Back: \t",$state[BACK],"C\n";
echo "Mininal temp is: ", $min_temp,"C\n";
echo "Current mode is: ",$heater_modes[$state[MODE]],"\n";

echo "============ Decision is:=============\n";

$suggested_mode = get_suggested_mode($state, $min_temp);

echo "Setting '",$heater_modes[$suggested_mode],"' mode:\n";
$answer = set_heater_mode($suggested_mode, $heater_modes[$state[MODE]], $heater_modes, $min_temp);
echo "======================================\n";
if ($state[MODE] != $suggested_mode){
    echo "New mode is: '",$heater_modes[$answer[0]],"'\n";
} else {
    echo "The mode change is not needed\n";
};

echo "======================================\n";
echo "(C) Litos printing company 2020 (f**k covid-19)\n";

exit(0);

function get_answer_for_AT_commant($at) {
    
    $number_of_try = 3;
    $answer = 'Bad request';
    $reg = "/\".+?\"/";
    
    while(($answer == 'Bad request' OR $answer == 'T')
            AND $number_of_try > 0) {
        echo "Tries left: ",$number_of_try, "\n"; 
        echo "...sent command: ", $at," \n";
        $answer = file_get_contents(URL.urlencode($at));
        if (strlen($answer) == 0) {
            emergency_exit('Connection timeout!');
        }
        preg_match_all($reg, $answer, $answer);
        $answer = str_replace('"','', $answer[0][1]);
        echo "...answer is: ",$answer,"\n";
        $number_of_try--;
    }
    
    if ($answer == 'Bad request' OR $answer == 'T') {
        emergency_exit('Bad command!');
    }
    echo "...done\n";
    return explode('|',$answer);
}

function emergency_exit($message){
    write_log('Error: '.$message);
    echo "======================================\n";
    echo "================ ERROR! ==============\n";
    exit($message."\n");
}

function save_text_line_to_file($filename, $text, $max_file_size){
    if (!file_exists($filename)) {
       $empty = fopen($filename, 'w');
       fclose($empty);
    }
    if (filesize($filename) > $max_file_size) {
        $content = explode("\n",file_get_contents($filename));
        $new_content = array_slice($content, -24 * 8);
        file_put_contents($filename, implode("\n", $new_content)."\n".$text."\n");
    } else {
        file_put_contents($filename, $text."\n", FILE_APPEND);
    }    
};

function write_log($text) {
    save_text_line_to_file(LOG_FILE_PATH, date('Y-m-d H:i:s').' '.$text, MAX_LOG_SIZE);
};

function save_state_log($state){
    $text = date('Y-m-d|H:i:s').'|'.implode('|', $state);
    save_text_line_to_file(STATE_LOG_FILE_PATH, $text, MAX_LOG_SIZE);
};


function set_heater_mode($mode, $current_mode, $heater_modes, $min_temp) {
    
    write_log('Min temp: '.$min_temp.' Set mode from '.$current_mode.' to '.$heater_modes[$mode]);
    
    return get_answer_for_AT_commant('AT set mode '.$mode);
}

function get_suggested_mode($state, $min_temp){
    
    if ($state[RUSLAN] >= $min_temp AND $state[FASMEB] >= $min_temp) {
        return OFF;
    }
    
    if ($state[OUTDOOR] < WINTER_TEMP){
        return STANDART;
    }
    
    if ($state[OUTDOOR] < $min_temp) {
        
        $mode = ECO;
        
        $delta_fasmeb = (float)$min_temp - (float)$state[FASMEB];
        $delta_ruslan = (float)$min_temp - (float)$state[RUSLAN];
        
        if ($delta_fasmeb > DELTA_FOR_ACTIVE_MODE) {
            echo "Need to add to FASMEB: ", $delta_fasmeb,"C\n";
            $mode = STANDART;
        };
        
        if ($delta_ruslan > DELTA_FOR_ACTIVE_MODE) {
            echo "Need to add to RUSLAN: ", $delta_ruslan,"C\n";
            $mode = STANDART;
        };
        
        return $mode;
    }
    
    return OFF;
}

