<?php

use App\Discount;
use Morilog\Jalali\Jalalian;

function update_setting($key, $value)
{
    $setting = \App\Setting::firstOrNew([
        'key' => $key
    ]);
    $setting->value = $value;
    $setting->save();
    return $setting;
}

function get_setting($key)
{
    return optional(\App\Setting::where('key', $key)->first())->value;
}

function message($message, $type = 'error')
{
    \Session::flash('message', $message);
    \Session::flash('type', $type);
}

function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function curl_get_file_size($url)
{
    // Assume failure.
    $result = -1;

    $curl = curl_init($url);

    // Issue a HEAD request and follow any redirects.
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Chrome');

    $data = curl_exec($curl);
    curl_close($curl);

    if ($data) {
        $content_length = "unknown";
        $status = "unknown";

        if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
            $status = (int)$matches[1];
        }

        if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
            $content_length = (int)$matches[1];
        }

        // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        if ($status == 200 || ($status > 300 && $status <= 308)) {
            $result = $content_length;
        }
    }

    return $result;
}

function twoDigitNumber($number)
{
    if ($number < 10) {
        $number = '0' . $number;
    }
    return $number;
}

function toJalali($date, $time = false)
{
    if ($date) {
        if (!is_string($date))
            $time = $date->format('H:i:s'). str_repeat('&nbsp;', 3);
        else
            $time = '';
        if (!is_numeric($date)) {
            $date = strtotime($date);
            $date = date('Y-m-d', $date);
        }



        $date = explode('-', $date);
        $date = \Morilog\Jalali\CalendarUtils::toJalali($date[0], $date[1], $date[2]);
        $date[1] = twoDigitNumber($date[1]);
        $date[2] = twoDigitNumber($date[2]);

        return $time . implode('/', $date);
    }
    return '---';
}
function toJalaliFormat($date, $format)
{
    return Jalalian::forge(strtotime($date))->format($format);
}


function toGregorian($date)
{

    try{
        if ($date==='0000/00/00'){
            return null;
        }
        $date = explode('/', $date);
        $date = array_map(function ($item){
            if (!is_numeric($item))
                return 1;
            return $item;
        },$date);
        $date = \Morilog\Jalali\CalendarUtils::toGregorian($date[0], $date[1], $date[2]);
        $date[1] = twoDigitNumber($date[1]);
        $date[2] = twoDigitNumber($date[2]);
        if (strlen($date[0])==2)
            return null;
        return implode('-', $date);
    }catch (\Exception $exception){
        return null;
    }
}

function convert2persian($string)
{
    $persinaDigits1 = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $persinaDigits2 = array('٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠');
    $allPersianDigits = array_merge($persinaDigits1, $persinaDigits2);
    $replaces = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    return str_replace($replaces, $allPersianDigits, $string);
}

function convert2english($string)
{
    $persinaDigits1 = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $persinaDigits2 = array('٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠');
    $allPersianDigits = array_merge($persinaDigits1, $persinaDigits2);
    $replaces = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    return str_replace($allPersianDigits, $replaces, $string);
}

function getDiffrenceBetweenArrays($arrayOne, $arrayTwo)
{
    $combined = array_merge($arrayOne, $arrayTwo);
    $intersect = array_intersect($arrayOne, $arrayTwo);
    $otherAuthors = array_diff($combined, $intersect);
    return $otherAuthors;
}

function get_challenges($year, $month)
{
    return \App\Challenge::where('year', $year)->where('month', $month)->get();
}

function strHas($string, $search, $caseSensitive = FALSE)
{
    if ($caseSensitive) {
        return strpos($string, $search) !== FALSE;
    } else {
        return strpos(strtolower($string), strtolower($search)) != FALSE;
    }
}

function convert_br_to_newline($text)
{
    $text = str_replace('<br>', "\n\n", $text);
    return html_entity_decode(str_replace('</br>', "\n", $text));
}

function replace_br_in_tr($html)
{
    $html = str_replace('<br>', '', $html[0]);
    $html = str_replace('<br/>', '', $html);
    $html = str_replace('<br />', '', $html);
    return $html;
}

function replace_width_with_style($html)
{
    $w = $html[2];
    $add = false;
    if (strHas($w,'>'))
        $add = true;
    if (!is_numeric($w)) {
        $w = str_replace(['"',"'",'t','d','>','<','/'],'',$w);
    }
    $html = '<td style="border:1px solid #707070;width:' . ($w ) . 'px"';
    if ($add)
        $html.='>';
    return $html;
}

function replace_enter_with_br($text) {
	return str_replace("\n","<br>",$text);
}

function replace_space_in_address_bar_with_dash($sentence) {
	$modifySentence =  str_replace(' ', '-', $sentence);
	$modifySentence =  str_replace('/', '-', $modifySentence);
	return $modifySentence;
}

function replace_slash_in_address_bar_with_dash($sentence) {
	$modifySentence =  str_replace('/', '-', $sentence);
	return $modifySentence;
}

function download_file($link, $path)
{
    $p = str_replace(basename($path), '', $path);
    if (!is_dir($p)) {
        File::makeDirectory($p, 0777, true, true);
    }

    $client = new \GuzzleHttp\Client();
    $client->request('GET', $link, [
        'sink' => $path,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0',
        ],
    ]);
}

function get_table_image($founded){
    $id = str_replace(['id','"','=',' '],'',$founded[1]);
    $tableImage = \App\TableImage::find($id);
    if (!$tableImage)
        return "";
    $tag =  '<div style="text-align: center;margin: 0 auto;justify-content:center;align-content: center"><img style="text-align: center;  margin-left: auto;
  margin-right: auto;
  width: 50%;" src="'.url($tableImage->url).'"/></div>';
    return $tag;
}

function convertFaNameToNumber($name){
    $number = $name;
    switch ($name){
        case "اول":
            $number = 1;
            break;
        case "دوم":
            $number = 2;
            break;
        case "سوم":
            $number = 3;
            break;
        case "چهارم":
            $number = 4;
            break;
        case "پنجم":
            $number = 5;
            break;
        case "ششم":
            $number = 6;
            break;
        case "هفتم":
            $number = 7;
            break;
        case "هشتم":
            $number = 8;
            break;
        case "نهم":
            $number = 9;
            break;
        case "دهم":
            $number = 10;
            break;
        case "یازدهم":
            $number = 11;
            break;
        case "دوازدهم":
            $number = 12;
            break;
        case "سیزدهم":
            $number = 13;
            break;
        case "چهاردهم":
            $number = 14;
            break;
        case "پانزدهم":
            $number = 15;
            break;
        case "شانزدهم":
            $number = 16;
            break;
        case "هفدهم":
            $number = 17;
            break;
        case "هیجدهم":
            $number = 18;
            break;
        case "نوزدهم":
            $number = 19;
            break;
        case "بیستم":
            $number = 20;
            break;
        case "بیست و یکم":
            $number = 21;
            break;
        case "بیست و دوم":
            $number = 22;
            break;
        case "بیست و سوم":
            $number = 23;
            break;
        case "بیست و چهارم":
            $number = 24;
            break;
        case "بیست و پنجم":
            $number = 25;
            break;
        case "بیست و ششم":
            $number = 26;
            break;
        case "بیست و هفتم":
            $number = 27;
            break;
        case "بیست و هشتم":
            $number = 28;
            break;
        case "بیست و نهم":
            $number = 29;
            break;
        case "سی ام":
            $number = 30;
            break;
        case "سی و یکم":
            $number = 31;
            break;
        case "سی و دوم":
            $number = 32;
            break;
        case "سی و سوم":
            $number = 33;
            break;
        case "سی و چهارم":
            $number = 34;
            break;
        case "سی و پنجم":
            $number = 35;
            break;
        case "سی و ششم":
            $number = 36;
            break;
        case "سی و هفتم":
            $number = 37;
            break;
        case "سی و هشتم":
            $number = 38;
            break;
        case "سی و نهم":
            $number = 39;
            break;
        case "چهلم":
            $number = 40;
            break;
        case "چهل و یکم":
            $number = 41;
            break;
        case "چهل و دوم":
            $number = 42;
            break;
        case "چهل و سوم":
            $number = 43;
            break;
        case "چهل و چهارم":
            $number = 44;
            break;
        case "چهل و پنجم":
            $number = 45;
            break;
        case "چهل و ششم":
            $number = 46;
            break;
        case "چهل و هفتم":
            $number = 47;
            break;
        case "چهل و هشتم":
            $number = 48;
            break;
        case "چهل و نهم":
            $number = 49;
            break;
        case "پنجاهم":
            $number = 50;
            break;
        case "پنجاه و یکم":
            $number = 51;
            break;
        case "پنجاه و دوم":
            $number = 52;
            break;
        case "پنجاه و سوم":
            $number = 53;
            break;
        case "پنجاه و چهارم":
            $number = 54;
            break;
        case "پنجاه و پنجم":
            $number = 55;
            break;
        case "پنجاه و ششم":
            $number = 56;
            break;
        case "پنجاه و هفتم":
            $number = 57;
            break;
        case "پنجاه و هشتم":
            $number = 58;
            break;
        case "پنجاه و نهم":
            $number = 59;
            break;
        case "شصتم":
            $number = 60;
            break;
        case "شصت و یکم":
            $number = 61;
            break;
        case "شصت و دوم":
            $number = 62;
            break;
        case "شصت و سوم":
            $number = 63;
            break;
        case "شصت و چهارم":
            $number = 64;
            break;
        case "شصت و پنجم":
            $number = 65;
            break;
        case "شصتت و ششم":
            $number = 66;
            break;
        case "شصت و هفتم":
            $number = 67;
            break;
        case "شصت و هشتم":
            $number = 68;
            break;
        case "شصت و نهم":
            $number = 69;
            break;
        case "هفتادم":
            $number = 70;
            break;
        case "هفتاد و یکم":
            $number = 71;
            break;
        case "هفتاد و دوم":
            $number = 72;
            break;
        case "هفتاد و سوم":
            $number = 73;
            break;
        case "هفتاد و چهارم":
            $number = 74;
            break;
        case "هفتاد و پنجم":
            $number = 75;
            break;
        case "هفتاد و ششم":
            $number = 76;
            break;
        case "هفتاد و هفتم":
            $number = 77;
            break;
        case "هفتاد و هشتم":
            $number = 78;
            break;
        case "هفتاد و نهم":
            $number = 79;
            break;
        case "هشتادم":
            $number = 80;
            break;
        case "هشتاد و یکم":
            $number = 81;
            break;
        case "هشتاد و دوم":
            $number = 82;
            break;
        case "هشتاد و سوم":
            $number = 83;
            break;
        case "هشتاد و چهارم":
            $number = 84;
            break;
        case "هشتاد و پنجم":
            $number = 85;
            break;
        case "هشتاد و ششم":
            $number = 86;
            break;
        case "هشتاد و هفتم":
            $number = 87;
            break;
        case "هشتاد و هشتم":
            $number = 88;
            break;
        case "هشتاد و نهم":
            $number = 89;
            break;
        case "نودم":
            $number = 90;
            break;
        case "نود و یکم":
            $number = 91;
            break;
        case "نود و دوم":
            $number = 92;
            break;
        case "نود و سوم":
            $number = 93;
            break;
        case "نود و چهارم":
            $number = 94;
            break;
        case "نود و پنجم":
            $number = 95;
            break;
        case "نود و ششم":
            $number = 96;
            break;
        case "نود و هفتم":
            $number = 97;
            break;
        case "نود و هشتم":
            $number = 98;
            break;
        case "نود و نهم":
            $number = 99;
            break;
        case "صدم":
            $number = 100;
            break;

    }
    return $number;
}

function sendMessage($data,$target){
//FCM api URL
    $url = 'https://fcm.googleapis.com/fcm/send';
//api_key available in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
    $server_key = 'AAAAcDeaVDc:APA91bHyVlMK2LPZ9k2DTl7835zS3e7QeNV3ugcHl_47_3bwpXhRTbF0nB8gSacAjWG3nhmEi8ZI-ieqvIVqXzsGXJhnkOM4P98AgRAInkN5KyBNvnn2Nm2FH1N-Cp_R5hIYTkWBF2yc';

    $fields = array();
    $fields['data'] = $data;
    if(is_array($target)){
        $fields['registration_ids'] = $target;
    }else{
        $fields['to'] = $target;
    }
        $fields['priority'] = 'high';
//header with content_type api key
    $headers = array(
        'Content-Type:application/json',
        'Authorization:key='.$server_key
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function convertAlphabetToNumber($wordNumber)
{

    $hundredsArray = [100 => 'یکصد', 200 => 'دویست', 300 => 'سیصد', 400 => 'چهارصد', 500 => 'پانصد', 600 => 'ششصد', 700 => 'هفتصد', 800 => 'هشتصد', 900 => 'نهصد'];
    $hundredsArray2 = [100 => 'یکصدم', 200 => 'دویستم', 300 => 'سیصدم', 400 => 'چهارصدم', 500 => 'پانصدم', 600 => 'ششصدم', 700 => 'هفتصدم', 800 => 'هشتصدم', 900 => 'نهصدم'];
    $hundredsArray3 = [100 => 'صد'];
    $hundredsArray4 = [100 => 'صدم'];

    $tensArray = [10 => 'دهم', 11 => 'یازدهم', 12 => 'دوازدهم', 13 => 'سیزدهم', 14 => 'چهاردهم', 15 => 'پانزدهم', 16 => 'شانزدهم', 17 => 'هفدهم', 18 => 'هجدهم', 19 => 'نوزدهم', 20 => 'بیستم', 30 => 'سی ام', 40 => 'چهلم', 50 => 'پنجاهم', 60 => 'شصتم', 70 => 'هفتادم', 80 => 'هشتادم', 90 => 'نودم'];
    $tensArray2 = [20 => 'بیست', 30 => 'سی', 40 => 'چهل', 50 => 'پنجاه', 60 => 'شصت', 70 => 'هفتاد', 80 => 'هشتاد', 90 => 'نود'];

    $oneArray = [1 => 'اول', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم', 5 => 'پنجم', 6 => 'ششم', 7 => 'هفتم', 8 => 'هشتم', 9 => 'نهم'];
    $oneArray2 = [1 => 'یکم', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم', 5 => 'پنجم', 6 => 'ششم', 7 => 'هفتم', 8 => 'هشتم', 9 => 'نهم'];

    $words = explode(" ", $wordNumber);
    $numberOne = null;
    $numberTwo = null;
    $numberThree = null;

    foreach ($words as $key => $word) {

        if (mb_substr($word, -1) == "و") {
            $words[$key] = mb_substr($word, 0, -1);
        };

        if (mb_substr($word, 0, 1) == "و") {
            $words[$key] = mb_substr($word, 1);
        };

        if ($word == null || $word == '') {
            continue;
        }

    }

    foreach ($words as $word) {
        if(in_array($word, $hundredsArray) || in_array($word, $hundredsArray2) || in_array($word, $hundredsArray3) || in_array($word, $hundredsArray4)) {
            $numberThree = array_search($word, $hundredsArray);
            if(!$numberThree) {
                $numberThree = array_search($word, $hundredsArray2);
                if(!$numberThree){
                    $numberThree = array_search($word, $hundredsArray3);
                    if(!$numberThree) {
                        $numberThree = array_search($word, $hundredsArray4);
                    }
                }
            }
        }


        if(in_array($word, $tensArray) || in_array($word, $tensArray2)) {
            $numberTwo = array_search($word, $tensArray);
            if(!$numberTwo) {
                $numberTwo = array_search($word, $tensArray2);
            }
        }

        if(in_array($word, $oneArray) || in_array($word, $oneArray2)) {
            $numberOne = array_search($word, $oneArray);
            if(!$numberOne) {
                $numberOne = array_search($word, $oneArray2);
            }
        }
    }



    if($numberThree && $numberTwo) {
        return $numberThree + $numberTwo + $numberOne;
    }

    if($numberThree && $numberOne) {
        return $numberThree + $numberOne;
    }

    if($numberTwo) {
        return $numberTwo + $numberOne;
    }

    return $numberOne;
}

function has_valid_discount($code){
    $discount = Discount::whereCode($code)->first();
    if (!$discount)
        return false;
    if ($discount->count == $discount->count_used)
        return false;
    if ($discount->has_used)
        return false;
    return $discount;
}

function clear_text($text){
    $text = str_replace('ي','ی',$text);
    $text = str_replace('ك','ک',$text);
    return $text;
}

function hexToString($hexString) {
    return pack("H*" , str_replace('%', '', $hexString));
}

function utf8_urldecode($str) {
    return html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($str)), null, 'UTF-8');
}

function _convert($content) {
    if(!mb_check_encoding($content, 'UTF-8')
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

        $content = mb_convert_encoding($content, 'UTF-8');

        if (mb_check_encoding($content, 'UTF-8')) {
            // log('Converted to UTF-8');
        } else {
            // log('Could not converted to UTF-8');
        }
    }
    return $content;
}

function unicode2html($str){
    // Set the locale to something that's UTF-8 capable
    setlocale(LC_ALL, 'en_US.UTF-8');
    // Convert the codepoints to entities
    $str = preg_replace("/u([0-9a-fA-F]{4})/", "&#x\\1;", $str);
    // Convert the entities to a UTF-8 string
    return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $str);
}

function chr_utf8($n,$f='C*'){
    return $n<(1<<7)?chr($n):($n<1<<11?pack($f,192|$n>>6,1<<7|191&$n):
        ($n<(1<<16)?pack($f,224|$n>>12,1<<7|63&$n>>6,1<<7|63&$n):
            ($n<(1<<20|1<<16)?pack($f,240|$n>>18,1<<7|63&$n>>12,1<<7|63&$n>>6,1<<7|63&$n):'')));
}

function codepoint_decode($str) {
    return json_decode(sprintf('"%s"', $str));
}
function shuffle_assoc($list) {
    if (!is_array($list)) return $list;

    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key) {
        $random[$key] = $list[$key];
    }
    return $random;
}
function test($term){
    // removing symbols used by MySQL
    $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~','*'];
    $term = str_replace($reservedSymbols, '', $term);

    $words = explode(' ', $term);

    foreach($words as $key => $word) {
        /*
         * applying + operator (required word) only big words
         * because smaller ones are not indexed by mysql
         */
        if(strlen($word) >= 1) {
            $words[$key] = '+' . $word . '*';
        }
    }

    $searchTerm = implode( ' ', $words);
    dd($searchTerm);
}
