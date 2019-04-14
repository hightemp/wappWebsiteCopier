<?php
 
//$iSharedMemoryLimit = (int) system("cat /proc/sys/kernel/shmmax");
//sudo sysctl -w kernel.shmmax=500000000

$iPID = getmypid();

function fnProcessExists($iPID) 
{
    return file_exists("/proc/{$iPID}");
}

if ($argv[1]=='main_loop') {

    $iKey = ftok(__FILE__, 1);

    $resID = shmop_open($iKey, 'a', 0644, 0);

    if (!$resID) {
        error_log(__LINE__.": Can't open block\n", 3, "errors.log");
        exit(1);
    }
    
    $sURL = shmop_read($resID, 0, shmop_size($resID));

}

if ($argv[1]=='parse') {

}

if (!empty($_POST['ajax'])) {
    $aResult = [
        'result' => 'ok',
        'message' => '',
        'data' => []
    ];
    
    try {
    
        if (!empty($_POST['main_loop_status'])) {
            $iMainLoopPIDKey = ftok(__FILE__, 2);
            
            $resID = shmop_open($iMainLoopPIDKey, 'a', 0644, 0);
            
            if (!$resID) {
                throw new Exception(__LINE__.": Can't read block\n");
            }
            
            $iMainLoopPID = (int) shmop_read($resID, 0, shmop_size($resID));
            
            $aResult['data'] => [
                'bIsRunning' => fnProcessExists($iMainLoopPID),
                'iPID' => $iMainLoopPID
            ];
            
            shmop_close($resID);
        }
        if (!empty($_POST['start'])) {
            
            $aMainLoopParameters = [];
            
            foreach ($_POST['aURL'] as $sURL) {
                $aMainLoopParameters[$sURL] = [
                    'aExclude' => $_POST[$sURL]['aExclude'],
                    'bSaveImages' => $_POST[$sURL]['bSaveImages']
                ]
            }
            
            $sBytes = json_encode($aMainLoopParameters);
            
            $iMainLoopParametersKey = ftok(__FILE__, 1);
            
            $resID = shmop_open($iMainLoopParametersKey, 'c', 0644, 0);
            
            if (shmop_write($resID, $sBytes, 0)!=strlen($sBytes)) {
                throw new Exception(__LINE__.": Can't write block\n");
            }
            
            shmop_close($resID);
        }
        
    } catch(Exception $oException) {
        error_log($oException->getMessage(), 3, "errors.log");
        $aResult['result'] = 'error';
        $aResult['message'] = $oException->getMessage();    
    }
    
    die(json_encode($aResult));
}
?>

<!--
$iKey = ftok(__FILE__, 1);

echo "pid:".$iPID."\n";

$resHandler = shmop_open($iKey, 'a', 0644, 4);

if (!$resHandler) {
    echo "Allocate memory\n";
    
    $resHandler = shmop_open($iKey, 'c', 0644, 4);
    
    if (!$resHandler) {
        die("Can't create block\n");
    }
    
    $sBytes = pack("l", $iPID);
    
    if (shmop_write($resHandler, $sBytes, 0)!=strlen($sBytes)) {
        die("Can't write to shared memory");
    }
    
    echo "Write: ".strlen($sBytes)." B\n";
}

if (!$resHandler) {
    die('Wrong shmop_open resource.');
}

$sBytes = shmop_read($resHandler, 0, 4);

echo "unpacked id:".unpack("l", $sBytes)[1]."\n";

/*
if (!shmop_delete($resHandler)) {
    echo "Can't delete block\n";
}
*/
shmop_close($resHandler);
-->
