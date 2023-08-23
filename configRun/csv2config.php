<?php
$csvs = array("test","BoatConfigDefine","BoatConfigThemeDefine","BoatSkillConfig_HP","BoatSkillConfig_DS","BoatSkillConfig_JG","BoatSkillConfig_QG","BoatSkillConfig_QX","BulletConfigDefine","LevelUpConfigDefine","RoleConfigDefine","SkillConfigDefine","BossPosConfigDefine","WorldMapConfigDefine","ExploreConfigDefine","CompositeConfigDefine","worldMap1","worldMap2","worldMap3","worldMap4","worldMap5","worldMap6","worldMap7","worldMap8","worldMap9","worldMap10");

$json_arr = array();
//语言包数据
$lang_arr = array();
//读取csv,生成sql
foreach ($csvs as $csv) {
    $arrCSV = array();
    $arrkey = array();
    // Open the CSV
    if (($handle = fopen("$csv.csv", "r")) !== FALSE) {
        echo $csv;
        // Set the parent array key to 0
        $key = 0;
        // While there is data available loop through unlimited times (0) using separator (,)
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            //加入key的名字
            if ($key == 0) {
                $c = count($data);
                for ($x = 0; $x < $c; $x++)
                    $arrkey[$x] = $data[$x];
                $key++;
                continue;
            }
            //跳过,中文名列
            if ($key == 1) {
                $key++;
                continue;
            }
			
			if (empty($data[0]) && empty($data[1])) {
				continue;
			}
            // Count the total keys in each row
            $length = count($data);
            //Populate the array
            for ($i = 0; $i < $length; $i++) {
                //处理skip
                if (!isset($arrkey[$i]) || $arrkey[$i] == 'S_skip') {
                    continue;
                }

                if (is_numeric($data[$i]) && isint($data[$i])) {
                    $data[$i] = (int)$data[$i];
                }

                //处理语言包
                $skey = explode('_', $arrkey[$i]);
                if ($csv == 'basic') {
                    $sidata = explode('_', $data[$i]);
                    if ($sidata[0] == 'JS') {
                        //$data[$i] = json_decode($sidata[1], true);
                    }
                }
                
                if ($skey[0] == 'L') {
                    //$data[$i] = iconv("GBK", "UTF-8", $lang_arr[$data[$i]]['content']);
                    //$data[$i] = iconv("GBK", "UTF-8", $data[$i]);
                }
                
                //处理json
                if ($skey[0] == 'JS') {
                    $data[$i] = json_decode($data[$i], true);
                }
                
                //处理单一序列化
                if ($skey[0] == 'SE') {
                    $data[$i] = Arr::_serial_to_array($data[$i]);
                }
                
                //处理多重序列化
                if ($skey[0] == 'MSE') {
                    $data[$i] = Arr::_multi_serial_to_array($data[$i]);
                }
				
				//重写key
				if (count($skey) > 1) {
					$line_key = $skey[1];
				} else {
					$line_key = $skey[0];
				}
                
                $arrCSV[$key - 2][$line_key] = $data[$i];
            }
            $key++;
        } // end while
        // Close the CSV file
        fclose($handle);
    } // end if

    //./phpconfig
    if (empty($argv[1])) {
        $output_dir = 'D:\Code\naruto_mobile\trunk\server\code\application\config\game';
    } else {
        $output_dir = './phpconfig';
    }

    if (!file_exists("$output_dir/$csv")) {
        mkdir("$output_dir/$csv", 0777);
    }
    $export_arr = array();
    foreach ($arrCSV as $key => $value) {
        $export_str = var_export($value, true);
        $export_str = "<?php defined ( 'SYSPATH' ) or die ( 'No direct access allowed.' );\r\n".'$config = '.$export_str.';';

        //存成php
        file_put_contents("$output_dir/$csv/{$value['id']}.php", $export_str);
    }
    
    //记录语言数据
    // if ($csv == 'lang') {
    //      $lang_arr = $export_arr;
    // }
    
    if ($csv != 'lang') {
        $json_arr[$csv] = $arrCSV;
    }

    @unlink("{$prestr}{$csv}.csv");
}

if (empty($argv[1])) {
    $output_dir = 'D:\Code\naruto_mobile\starling\bin\assets\config';
} else {
    $output_dir = '.';
}

$json = json_encode($json_arr, JSON_UNESCAPED_UNICODE);
file_put_contents("$output_dir/config.bin", ob_gzip($json));
file_put_contents("./config.php", $json);

echo 'csv 2 phpconfig&json OK';

function ob_gzip($content)
{
	if(extension_loaded("zlib") )
    {
		$content = gzcompress($content,9);
    }
    return $content;
}

function isint($i)
{
    if(floor($i) == $i){
        return true;
    }else{
        return false;
    }
}

class Arr {

    // 序列转换数组
    public static function _serial_to_array($strSerial, $strSplitMain = ';', $strSplitSub = ':', $mergeNumeric = true) {
        $arrResult = array();
        if ($strSerial) {
            $arrRand = explode($strSplitMain, $strSerial);
            while (list ( $key, $item ) = @each($arrRand)) {
                if (!$item)
                    continue;
                $arrItem = explode($strSplitSub, $item);
                $arrItem[0] = str_replace(array("\n", "\r"), '', $arrItem[0]);

                // 是否合并数值型值
                if ($mergeNumeric && isset($arrResult[$arrItem[0]]) && is_numeric($arrResult[$arrItem[0]]) && is_numeric($arrItem[1])) {
                    $arrResult[$arrItem[0]] += $arrItem[1];
                } else {
                    $arrResult[$arrItem[0]] = $arrItem[1];
                }
            }
        }
        return $arrResult;
    }

    // 数组转换序列
    public static function _array_to_serial($array, $strSplitMain = ';', $strSplitSub = ':') {
        while (list ( $key, $item ) = @each($array)) {
            $array[$key] = $key . $strSplitSub . $item;
        }
        $strSerial = self::_join($strSplitMain, $array);
        return $strSerial;
    }

    // 多重序列转换数组
    public static function _multi_serial_to_array($strSerial, $lineSplit = "\n", $idSplit = '/', $strSplitMain = ';', $strSplitSub = ':', $mergeNumeric = true) {
        $arrResult = array();
        if ($strSerial) {
            $strSerial = str_replace("\r", '', $strSerial);
            if ($strSerial && $entry = explode($lineSplit, $strSerial)) {
                while (list ( $key, $item ) = @each($entry)) {
                    $temp = explode($idSplit, $item);
                    if (isset($temp[0]) && isset($temp[1]))
                        $arrResult[$temp[0]] = self::_serial_to_array($temp[1], $strSplitMain, $strSplitSub, $mergeNumeric);
                }
            }
        }
        return $arrResult;
    }

    // 数组转换多重序列
    public static function _array_to_multi_serial($array, $lineSplit = "\n", $idSplit = '/', $strSplitMain = ';', $strSplitSub = ':') {
        while (list ( $key, $item ) = @each($array)) {
            $array[$key] = $key . $idSplit . self::_array_to_serial($item, $strSplitMain, $strSplitSub);
        }
        $strSerial = self::_join($lineSplit, $array);
        return $strSerial;
    }

    // 连接数组并忽略空键值
    public static function _join($char, $array) {
        @reset($array);
        while (list ( $key, $item ) = @each($array)) {
            if (strval($item) == '') {
                unset($array[$key]);
            }
        }
        $str = @join($char, $array);
        return $str;
    }

    public static function _string_to_array($str, $split = ',') {
        if (empty($str)) {
            return array();
        }
        $array = explode($split, $str);
        return $array;
    }

    public static function _array_to_string($array, $split = ',') {
        $str = implode($split, $array);
        return $str;
    }

    //随即返回数组
    public static function _array_rand($data) {
        return $data[array_rand($data)];
    }

}
