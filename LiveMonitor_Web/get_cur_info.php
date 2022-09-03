<?php

// $unicode_str: 如"\u54c8\u54c8"转换完后返回:"哈哈" 
function unicode_to_gbk2312($unicode_fmtstr)
{
    $unicode_fmtstr = '"'.$unicode_fmtstr.'"';
    $unicode_str = json_decode($unicode_fmtstr);
    return iconv("UTF-8", "GB2312//IGNORE", $unicode_str);   
}

# Return: true or false
function get_voteNum($page_contents, &$out_voteNum)
{
    // 首先提取出voteNum项：例: "voteNum":"189"
    $rule  = "/\"voteNum\"\:\"(\d{1,})\"/"; 
    $math_count = preg_match($rule, $page_contents, $result);
    if($math_count == 0)
    {
        return false;
    }

    // 例:189

    $out_voteNum = $result[0];
    return true;
}

// 读取PublicInfo页中的: 关注数、砖石数
// return: true or false
function getNumbers(&$out_voteNum, &$out_followNum)
{
    try
    {
        // 读取页,该页包含: 关注数、砖石数
        $publicInfo_url = "http://q.jia.360.cn/public/getPublicInfo?from=mpc_ipcam_web&sn=36030001713&_=1443971648114";
        $publicInfo_contents = @file_get_contents($publicInfo_url);

        // 转为json.
        $publicInfo_Json = json_decode($publicInfo_contents);

        // out：砖石数
        $out_voteNum = $publicInfo_Json->voteNum;
        // out: 关注数
        $out_followNum = $publicInfo_Json->followNum;
        return true;
    }
    //捕获异常
    catch(Exception $e)
    {
        return false;
    }
}

// 读取CommentsList也中: 最新(最后发表)一条评论的时间
// return: true or false
function getLastCommentTime(&$out_lastCommentTime, &$out_lastCommentContent)
{
    try
    {
        // 读取页,该页包含: 关注数、砖石数
        $commentsList_url = "http://q.jia.360.cn/comment/getCommentsList?sn=36030001713&page=0&_=1443971949765";
        $commentsList_contents = @file_get_contents($commentsList_url);

        // 转为json.
        $commentsList_Json = json_decode($commentsList_contents);
        if(0 == $commentsList_Json->total)
        {
            $lastCommentTime = "-1";   // 当还没有评论时, 将时间返回为-1
            return true;
        }
        // out: 最新一条评论时间
        $out_lastCommentTime = $commentsList_Json->data[0]->create_time;
        $out_lastCommentContent = unicode_to_gbk2312($commentsList_Json->data[0]->comment);
        return true;
    }
    //捕获异常
    catch(Exception $e)
    {
        return false;
    }
}


function readFileData($fileName, $readSize, &$out_fileData)
{
    try
    {
        $file = fopen($fileName, 'r');
        flock($file, LOCK_EX);      //锁定文件，避免读写
        $out_fileData = fread($file, $readSize);
        flock($file, LOCK_UN);      //解锁
        fclose($file);              //关闭程序流
        return true;
    }
    //捕获异常
    catch(Exception $e)
    {
        return false;
    }
}


function writeFileData($fileName, $fileData)
{
    try
    {
        $file = fopen($fileName, 'w');
        flock($file, LOCK_EX);      //锁定文件，避免读写
        fwrite($file, $fileData);
        flock($file, LOCK_UN);      //解锁
        fclose($file);              //关闭程序流
        return true;
    }
    //捕获异常
    catch(Exception $e)
    {
        return false;
    }
}

// 在$strContentStr里寻找是否存在$aryKeywords中的关键字. 如存在,返回keyword的index.不存在: -1
function findKeywords($strContentStr, $aryKeywords)
{
    $index = 0;
    foreach ($aryKeywords as $keyWord){ 
        // echo "Loop:".$keyWord;
        if(strstr($strContentStr, $keyWord))
        {
             return $index;
        }
        $index ++; 
    } 
    return -1;
}

// return: -1.检查出错 0.无更新  1.新钻石  2.新评论(含触发关键字)  3.新关注
function check_status()
{
    /* 检查是否更新,优先级从低到高: 新砖石 新评论(含触发关键字) 新关注  */
    $origValueFileName = "orig_value.txt";
    $lastCheckTime = "last_check_time.log";
    $commentKeywords = array("跳起来","跳一段","跳舞","跳支舞","跳个舞","扭一个","跳一个","扭屁股","摇摆","嗨起来");
    // 读取: 关注数、砖石数
    if(false == getNumbers($voteNum, $followNum))
    {
        return -1;
    }
    //echo "voteNum:", $voteNum, " followNum:", $followNum, "\r\n";

    // 读取: 最新一条评论时间
    
    if(false == getLastCommentTime($lastCommentTime, $lastCommentContent))
    {
        return -1;
    }
    // echo "lastCommentTime:", $lastCommentTime, "\r\n";
    // echo "lastCommentContent:", $lastCommentContent, "\r\n";
    // 读取旧值文件
    // 文件格式: "voteNum|lastCommentTime|followNum"
    $readSize = 0x50;
    if(readFileData($origValueFileName, $readSize, $origValues))
    {
        $aryValues = explode('|', $origValues);
        //echo $origValues,"\r\n";
        //echo count($aryValues),"\r\n";
        //echo $aryValues[0],"\r\n";
        // 如果分割后, 不足3个值, 则失败。
        if(3 > count($aryValues))
        {
            return -1;
        }
        $orig_voteNum = $aryValues[0];
        $orig_lastCommentTime = $aryValues[1];
        $orig_followNum = $aryValues[2];

    }
    else
    {
        //读失败, 可能为首次读取该文件(还未创建),所以使用默认值
        $orig_voteNum = "-1";
        $orig_lastCommentTime = "1970-01-01 00:00:00";
        $orig_followNum = "-1";
    }
    
    // 比较 关注数、砖石数、最新评论(含触发关键字) 是否更新了！
    $status_code = 0;       //没有更新:0, 否则根据优先级按序号递增：1.新砖石 2.新评论 3.新关注
    if($voteNum != $orig_voteNum){$status_code = 1;}
    if($lastCommentTime != $orig_lastCommentTime && \
       -1 != findKeywords($lastCommentContent, $commentKeywords)) // 当评论时间更新,并且最新评论内容命中了"跳舞"等关键字, 就激活触发.
    {
        $status_code = 2;
        // echo "命中了:".$commentKeywords[findKeywords($lastCommentContent, $commentKeywords)];
    }
    //if($followNum != $orig_followNum){$status_code = 3;}

    // 将修改后的值更新到文件
    if(0 != $status_code)
    {
        $newValues = $voteNum."|".$lastCommentTime."|".$followNum;

        if(false == writeFileData($origValueFileName, $newValues))
        {
            // 如果新值写入失败, 就返回$status_code为0。 因为如果继续返回非0, 单片机会触发动作。
            // 并且在下一轮请求时, 必然又会返回非0(因为值未更新),会造成单片机重复执行动作。
            $status_code = 0;
        }
    }

    // 更新最后检查时间"last_check_time.log"
    writeFileData($lastCheckTime, date('y-m-d h:i:s',time()) );

    return $status_code;
}

/*
// 只有当数据更新或出错时，才返回，否则一直等待，模拟长连接(服务器不支持长连接，只能用阻塞方式来实现)。
while(1)
{
    $status_code = check_status();
    if(0 != $status_code)
    {
         echo $status_code;
         break;
    }
    sleep(3);
}*/
$status_code = check_status();
echo $status_code;

?>