<?php

    header('Content-Type: text/plain');

    $temp = round($_REQUEST['temp']*10)/10;
    
    if($_REQUEST['temp'] < 5) $temp = 5;    /* minimal temp limit in degree */
    if($_REQUEST['temp'] > 10) $temp = 10;  /* maximal temp limit in degree */
    
    $command = "php /var/www/html/naumovich/naumovich3.php ".$temp;
    $answer = shell_exec("php /var/www/html/naumovich/naumovich3.php ".$temp);
    
    echo $answer;

