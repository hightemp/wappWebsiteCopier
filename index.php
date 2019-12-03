<?php
 
//$iSharedMemoryLimit = (int) system("cat /proc/sys/kernel/shmmax");
//sudo sysctl -w kernel.shmmax=500000000

$iPID = getmypid();

function fnReadSharedVar($sVarName)
{
    $iKey = ftok(__FILE__, $sVarName);
            
    $resID = shmop_open($iKey, 'a', 0644, 0);
    
    if (!$resID) {
        throw new Exception(__LINE__.": Can't read block '$sVarName'\n");
    }
    
    $mResult = json_decode(shmop_read($resID, 0, shmop_size($resID)), true);
        
    shmop_close($resID);
    
    return $mResult;
}

function fnWriteSharedVar($sVarName, $mVarValue)
{
    $sBytes = json_encode($mVarValue);
            
    $iKey = ftok(__FILE__, $sVarName);
    
    $resID = shmop_open($iKey, 'c', 0644, 0);
    
    if (shmop_write($resID, $sBytes, 0)!=strlen($sBytes)) {
        throw new Exception(__LINE__.": Can't write block '$sVarName'\n");
    }
    
    shmop_close($resID);
}

function fnProcessExists($iPID) 
{
    return file_exists("/proc/{$iPID}");
}

function fnGetMainLoopPID()
{
    return (int) fnReadSharedVar('main_loop_pid');
}

function fnIsMainLoopProccessRunning()
{
    return fnProcessExists(fnGetMainLoopPID());
}

if ($argv[1]=='main_loop') {
    
    try {

        fnWriteSharedVar('main_loop_pid', $iPID);
        
        while (true) {
            $aMainLoopParameters = fnReadSharedVar('main_loop_parameters');
            
            if ($aMainLoopParameters['bRun']==false) {
                break;
            }
        }
        
    } catch(Exception $oException) {
        error_log(date("d.m.Y H:i:s")." $iPID ".$oException->getMessage(), 3, "main_loop_errors.log");
    }
}

if ($argv[1]=='parse') {

}

if (!empty($_POST['ajax'])) {
    $aResult = [
        'status' => 'ok',
        'message' => '',
        'data' => []
    ];
    
    try {
    
        if (!empty($_POST['main_loop_status'])) {
            $iMainLoopPID = fnGetMainLoopPID();
            
            $aResult['data'] = [
                'bIsRunning' => fnProcessExists($iMainLoopPID),
                'iPID' => $iMainLoopPID
            ];
        }
        if (!empty($_POST['start'])) {
            
            $aMainLoopParameters = [
                'bRun' => true
            ];
            
            foreach ($_POST['aURL'] as $sURL) {
                $aMainLoopParameters['aURLs'][$sURL] = [
                    'aExclude' => $_POST[$sURL]['aExclude'],
                    'bSaveImages' => $_POST[$sURL]['bSaveImages']
                ];
            }
            
            fnWriteSharedVar('main_loop_parameters', $aMainLoopParameters);
            
            $sExecLine = 'php '.__FILE__.' main_loop';
            if (pcntl_exec($sExecLine)===false) {
                throw new Exception(__LINE__.": Can't start proces '$sExecLine'\n");
            }
        }
        
    } catch(Exception $oException) {
        error_log(date("d.m.Y H:i:s")."  ".$oException->getMessage(), 3, "errors.log");
        $aResult['status'] = 'error';
        $aResult['message'] = $oException->getMessage();    
    }
    
    die(json_encode($aResult));
}
?>

<style>
.block { display: block; }
.inline_block { display: inline-block; }
.float_left { float: left; }
.p10 { padding: 10px; }
.p10_10_0_10 { padding: 10px 10px 0px 10px; }
.w100 { width: 100%; }
.w30 { width: 29% }
.w50 { width: 48% }
.main-block { text-align: center }
.center-block { display: inline-block; min-width: 600px; vertical-align: top; }
.main-block > * { text-align: left; }
</style>

<div class="main-block">
    <div class="center-block">
        <div class="float_left w30">
            <div class="p10_10_0_10">
                <input class="w100 block" type="text" id="website-url" placeholder="http://website.com/">
            </div>
            <div class="p10_10_0_10">
                <button class="w50">Add</button>
                <button class="w50">Remove</button>
            </div>
            <div class="p10_10_0_10">
                <select class="w100 block" size=10>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
            </div>
        </div>
        <div class="float_left w30">
            <div class="p10_10_0_10">
                <input class="w100 block" type="text" id="options-website-url" placeholder="http://website.com/">
            </div>
        </div>
        <div class="float_left w30">
            <div class="p10_10_0_10">
                <div>Processes count: <span id="processes-count"></span></div>
                <div>Processes: </div>
                <ul id="processes-list">
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    function fnAjax(oData)
    {
        "use strict";

        if (oData.url === undefined) {
            return false;
        }
  
        oMIMEs = {
            'txt'  : 'text/plain',
            'json' : 'application/json',
            'xml'  : 'application/xml'
        };
        
        var sURL      = oData.url === undefined ? false : oData.url;
        var sMethod   = oData.method === undefined ? 'GET' : oData.method.toUpperCase();
        var sType     = oData.type === undefined ? 'txt' : oData.type.toLowerCase();
        var sMIMEType = oMIMEs[oData.type];
        var oHeaders  = oData.headers === undefined ? {} : oData.headers;
        var oSendData = oData.data === undefined ? false : oData.data;
        var fnSuccesscallback = oData.success !== undefined ? oData.success : function() {};
        var fnErrorcallback   = oData.error !== undefined ? oData.error : function() {};
        
        var oAjaxObject = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : (XMLHttpRequest && new XMLHttpRequest()) || null;
        var iAjaxTimeout = window.setTimeout(function () {
            ajax.abort();
        }, 6000);
        
        if (sMethod === 'GET') {
            oAjaxObject.open('GET', sURL, true);
            majax.overrideMime(ajax, mimetype);
            majax.setReqHeaders(ajax, header);
            oAjaxObject.send();
        } else if (sMethod === 'POST') {
            oAjaxObject.open('POST', sURL, true);
            majax.overrideMime(ajax, mimetype);
            majax.setReqHeaders(ajax, header);
            oAjaxObject.send(sendstring);
        } else {
            if (sMethod === 'HEAD') {
                mimetype = 'none';
            }
            oAjaxObject.open(sMethod, sURL, true);
            majax.overrideMime(ajax, mimetype);
            majax.setReqHeaders(ajax, header);
            oAjaxObject.send();
        }
    }
</script>

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
