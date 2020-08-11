<?php

namespace apimediator;

/**
 * ChunkUploadHandler
 *
 * Handles the chunk upload of the file. Also exposes method to inject file in the flow of video server api
 */
class ChunkUploadHandler
{
    protected $path    = '';
    protected $logPath = './logs/upload_log.log';
    protected $chunkNumber = 0;


    public function __construct($uploadPath)
    {
        $this->path    = $uploadPath;
    }

    /**
     * onRequest
     *
     * @return void
     */
    public function onRequest()
    {
        // var_dump($_FILES);die;

        $start = intval($_REQUEST['start']);
        $end   = intval($_REQUEST['end']);
        $chunkSize  = $_REQUEST['chunkSize'];
        $name       = $_REQUEST['filename'];
        $estimated  = $_REQUEST['estimated'];
        $this->chunkNumber = $_REQUEST['number'];

        $out = fopen($this->path . $name, "ab");

        if ($out !== false) {
            $in = @fopen($_FILES['chunk']['tmp_name'], "rb");
            if ($in !== false) {
                while ($buff = fread($in, $chunkSize)) {
                    fwrite($out, $buff);
                }
                @fclose($in);
            } else {
                file_put_contents(
                    $this->logPath,
                    '[' . date("d-m-Y H:m:s") . '] ERROR. ' . $name . ' No se grabo chunk ' .
                        $this->chunkNumber . " reitentando. \n",
                    FILE_APPEND
                );
                @fclose($out);
                die(json_encode(
                        [
                            "success" => false,
                            "msg" => "Failed to open input stream inner " . $_FILES['chunk']['tmp_name']
                        ]
                    ));
            }
            @fclose($out);
            @unlink($_FILES['chunk']['tmp_name']);
        } else {
            file_put_contents($this->logPath, '[' . date("d-m-Y H:m:s") .
                '] ERROR. ' . $name .
                ' No se pudo abrir archivo de acumulacion de chunks ' .
                $this->path . '. No se grabo chunk ' . $this->chunkNumber .
                "  \n", FILE_APPEND);
            @fclose($out);
            die(json_encode(["success" => false, "msg" => "Failed to open output stream"]));
        }

        @fclose($out);
        //time_nanosleep(0, 100000000);
        die(json_encode(['success' => true, 'chunk' => $this->chunkNumber]));
    }


    /**
     * passToApi
     *
     * @return void
     */
    public function passToApi()
    {

        //var_dump($_REQUEST);
        //die;
        $idInst    = $_REQUEST['idInstitution'];
        $shortName = $_REQUEST['shortname'];
        $userId    = $_REQUEST['ui'];
        $username  = base64_decode($_REQUEST['un']);
        $usermail  = base64_decode($_REQUEST['um']);
        $courseUrl = $_REQUEST['cc'];
        $S3_provider = 5; /* Id of the S3 provider in CTV webApp */

        $userPart = "";
        $name  = $_REQUEST['filename'];
        $data = array(
            "id"        => $idInst,
            "filename"  => $name,
            "tempfile"  => $this->path . $name,
            "shortname" => $shortName,
            "videoservice_storage_provider" => $S3_provider,
        );

        if (!empty($userId)) {
            $userId = intval($userId);
            $userPart .= "userid/{$userId}/";
        }
        if (!empty($username)) {
            $username = urlencode($username);
            $userPart .= "username/{$username}/";
        }
        if (!empty($usermail)) {
            $usermail = urlencode($usermail);
            $userPart .= "usermail/{$usermail}/";
        }
        if (!empty($usermail)) {
            $usermail = ($courseUrl);
            $userPart .= "course/{$courseUrl}/";
        }

        //$uri  = "http://192.168.55.10:3000/api/v1/institutions/{$idInst}/{$userPart}video/upload";
        $uri  = "http://video.classroomtv.com/api/v1/institutions/{$idInst}/{$userPart}video/upload";
        //die($uri);

        try {
            file_put_contents($this->logPath, '[' . date("d-m-Y H:m:s") . '] API REQUEST ON. URI.  ' . $uri . " \n", FILE_APPEND);
            file_put_contents($this->logPath, '[' . date("d-m-Y H:m:s") . '] API REQUEST ON. DATA. ' . json_encode($data) . " \n", FILE_APPEND);
        } catch (\Exception $e) {
        }

        $content = json_encode($data);
        //die($content);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:multipart/form-data"
        ));


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $info   = curl_getinfo($ch);

        $res    = json_decode($result);
        curl_close($ch);

        //$successDeletion = @unlink($this->path . $name);

        file_put_contents($this->logPath, '[' . date("d-m-Y H:m:s") . '] API REQUEST ON. RESPONSE. ' . $result . " \n", FILE_APPEND);

        // Use this output in case of debugging
        die(json_encode(array("result" => $result, "info" => $info, "data" => $content, "uri" => $uri, "successDeletion" => true)));
        // Use this output for normal cases
        //die(json_encode(array("result" => $result, "successDeletion" => true)));
    }


    /**
     * requestDeletion
     *
     * @return void
     */
    public function requestDeletion()
    {
        $name    = $_REQUEST['filename'];
        $success = @unlink($this->path . $name);

        file_put_contents($this->logPath, '[' . date("d-m-Y H:m:s") . '] ON REQUEST DELETION. ' . $success . " \n", FILE_APPEND);
        die(json_encode(array("success" => $success)));
    }
}

