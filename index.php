<?php
/**
 * @package Sdori.ca
 * @author 7lsu
 * @version 1.0.0
 * @link https://www.7lsu.com
 */
header('Access-Control-Allow-Origin:*');
header('Content-Type:application/json; charset=utf-8');

$user = isset($_GET['user']) ? $_GET['user'] : "";
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : "";
$token = isset($_GET['token']) ? $_GET['token'] : "";

if (empty($user) && empty($token)) {
    die(json_encode(
        array(
            'code' => 400,
            'msg' => '请输入USER or TOKEN'
        ),
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ));
}

if ($token == "") {
    $arr = array('account' => $user, 'secret' => $pwd);

    $res = json_decode(SdoricaLoginCurl($arr, 'https://2x0x0-api-phoebe.rayark.net/service/email/authenticate'), true);

    if (!isset($res['auth_code'])) {
        die(json_encode(
            array(
                'code' => 401,
                'msg' => $res['error']
            ),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
    }

    $auth_code = $res['auth_code'];

    $arr = array('app_key' => 'W0E8/FqmdZIOdQNwIa+4Wg', 'service_token' => $auth_code);

    $res = json_decode(SdoricaLoginCurl($arr, 'https://2x0x0-api-phoebe.rayark.net/service/email/login'), true);

    if (!isset($res['access_token'])) {
        die(json_encode(
            array(
                'code' => 401,
                'msg' => $res['error']
            ),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
    }

    $access_token = $res['access_token'];
} else {
    $access_token = $token;
}

$res = json_decode(SdoricaVoteCurl('', 'https://kudos.rayark.net/player/share', $access_token), true);

if (!isset($res['vote']) || $res['vote'] <= 0) {
    die(json_encode(
        array(
            'code' => 402,
            'msg' => '今日票数已用完~',
            'token' => $access_token
        ),
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ));
}

while (true) {
    $res = json_decode(SdoricaVoteCurl(json_encode(array('name' => 'leah')), 'https://kudos.rayark.net/vote', $access_token), true);
    if (!isset($res['history'])) {
        if ($res['error'] == 'QUANTITY_NOT_ENOUGH') {
            $msg = '今日投票已完成~';
        } else {
            $msg = $res['error'];
        }
        die(json_encode(
            array(
                'code' => 200,
                'msg' => $msg,
                'token' => $access_token
            ),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
    }
}

function SdoricaVoteCurl($post_data, $url, $token, $ifurl = '', $UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "access-token: " . $token
    ]);
    if ($ifurl != '') {
        curl_setopt($curl, CURLOPT_REFERER, $ifurl);
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function SdoricaLoginCurl($post_data, $url, $ifurl = '', $UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36')
{
    $data = '';
    $id = uniqid();
    buildData_fun($post_data, $id, $data);
    $data .=  "--" . $id . "--";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Content-Type: multipart/form-data; boundary=" . $id
    ]);
    if ($ifurl != '') {
        curl_setopt($curl, CURLOPT_REFERER, $ifurl);
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function buildData_fun($param, $id, &$data, &$path = '', $iii = 0)
{
    if (isset($param['filename'])) {
        #正常时是这样传送file文件的
        #$file = curl_file_create(ROOT_PATH . '/public/154106488401000050.pdf',"application/octet-stream");

        $data .=  "--" . $id . "\r\n"
            . 'Content-Disposition: form-data; name="' . $path . '"; filename="' . $param['filename'] . '"' . "\r\n"
            . 'Content-Type: application/octet-stream' . "\r\n\r\n";

        $data .= $param['file'] . "\r\n";
        return;
    }
    if (is_array($param) && $path != '') {
        $data .= "--" . $id . "\r\n" . 'Content-Disposition: form-data; name="' . $path . "\"\r\n\r\n" . $content . "\r\n";
    }
    foreach ($param as $name => $content) {
        if ($path == '') {
            $path1 = $name;
        } else {
            $path1 = $path . "[" . $name . "]";
        }
        if (is_array($content)) {
            buildData_fun($content, $id, $data, $path1);
        } else {
            $data .= "--" . $id . "\r\n"
                . 'Content-Disposition: form-data; name="' . $path1 . "\"\r\n\r\n"
                . $content . "\r\n";
        }
    }
    return;
}