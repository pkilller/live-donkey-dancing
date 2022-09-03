<?php

// $unicode_str: ��"\u54c8\u54c8"ת����󷵻�:"����" 
function unicode_to_gbk2312($unicode_fmtstr)
{
    $unicode_fmtstr = '"'.$unicode_fmtstr.'"';
    $unicode_str = json_decode($unicode_fmtstr);
    return iconv("UTF-8", "GB2312//IGNORE", $unicode_str);   
}

# Return: true or false
function get_voteNum($page_contents, &$out_voteNum)
{
    // ������ȡ��voteNum���: "voteNum":"189"
    $rule  = "/\"voteNum\"\:\"(\d{1,})\"/"; 
    $math_count = preg_match($rule, $page_contents, $result);
    if($math_count == 0)
    {
        return false;
    }

    // ��:189

    $out_voteNum = $result[0];
    return true;
}

// ��ȡPublicInfoҳ�е�: ��ע����שʯ��
// return: true or false
function getNumbers(&$out_voteNum, &$out_followNum)
{
    try
    {
        // ��ȡҳ,��ҳ����: ��ע����שʯ��
        $publicInfo_url = "http://q.jia.360.cn/public/getPublicInfo?from=mpc_ipcam_web&sn=36030001713&_=1443971648114";
        $publicInfo_contents = @file_get_contents($publicInfo_url);

        // תΪjson.
        $publicInfo_Json = json_decode($publicInfo_contents);

        // out��שʯ��
        $out_voteNum = $publicInfo_Json->voteNum;
        // out: ��ע��
        $out_followNum = $publicInfo_Json->followNum;
        return true;
    }
    //�����쳣
    catch(Exception $e)
    {
        return false;
    }
}

// ��ȡCommentsListҲ��: ����(��󷢱�)һ�����۵�ʱ��
// return: true or false
function getLastCommentTime(&$out_lastCommentTime, &$out_lastCommentContent)
{
    try
    {
        // ��ȡҳ,��ҳ����: ��ע����שʯ��
        $commentsList_url = "http://q.jia.360.cn/comment/getCommentsList?sn=36030001713&page=0&_=1443971949765";
        $commentsList_contents = @file_get_contents($commentsList_url);

        // תΪjson.
        $commentsList_Json = json_decode($commentsList_contents);
        if(0 == $commentsList_Json->total)
        {
            $lastCommentTime = "-1";   // ����û������ʱ, ��ʱ�䷵��Ϊ-1
            return true;
        }
        // out: ����һ������ʱ��
        $out_lastCommentTime = $commentsList_Json->data[0]->create_time;
        $out_lastCommentContent = unicode_to_gbk2312($commentsList_Json->data[0]->comment);
        return true;
    }
    //�����쳣
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
        flock($file, LOCK_EX);      //�����ļ��������д
        $out_fileData = fread($file, $readSize);
        flock($file, LOCK_UN);      //����
        fclose($file);              //�رճ�����
        return true;
    }
    //�����쳣
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
        flock($file, LOCK_EX);      //�����ļ��������д
        fwrite($file, $fileData);
        flock($file, LOCK_UN);      //����
        fclose($file);              //�رճ�����
        return true;
    }
    //�����쳣
    catch(Exception $e)
    {
        return false;
    }
}

// ��$strContentStr��Ѱ���Ƿ����$aryKeywords�еĹؼ���. �����,����keyword��index.������: -1
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

// return: -1.������ 0.�޸���  1.����ʯ  2.������(�������ؼ���)  3.�¹�ע
function check_status()
{
    /* ����Ƿ����,���ȼ��ӵ͵���: ��שʯ ������(�������ؼ���) �¹�ע  */
    $origValueFileName = "orig_value.txt";
    $lastCheckTime = "last_check_time.log";
    $commentKeywords = array("������","��һ��","����","��֧��","������","Ťһ��","��һ��","Ťƨ��","ҡ��","������");
    // ��ȡ: ��ע����שʯ��
    if(false == getNumbers($voteNum, $followNum))
    {
        return -1;
    }
    //echo "voteNum:", $voteNum, " followNum:", $followNum, "\r\n";

    // ��ȡ: ����һ������ʱ��
    
    if(false == getLastCommentTime($lastCommentTime, $lastCommentContent))
    {
        return -1;
    }
    // echo "lastCommentTime:", $lastCommentTime, "\r\n";
    // echo "lastCommentContent:", $lastCommentContent, "\r\n";
    // ��ȡ��ֵ�ļ�
    // �ļ���ʽ: "voteNum|lastCommentTime|followNum"
    $readSize = 0x50;
    if(readFileData($origValueFileName, $readSize, $origValues))
    {
        $aryValues = explode('|', $origValues);
        //echo $origValues,"\r\n";
        //echo count($aryValues),"\r\n";
        //echo $aryValues[0],"\r\n";
        // ����ָ��, ����3��ֵ, ��ʧ�ܡ�
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
        //��ʧ��, ����Ϊ�״ζ�ȡ���ļ�(��δ����),����ʹ��Ĭ��ֵ
        $orig_voteNum = "-1";
        $orig_lastCommentTime = "1970-01-01 00:00:00";
        $orig_followNum = "-1";
    }
    
    // �Ƚ� ��ע����שʯ������������(�������ؼ���) �Ƿ�����ˣ�
    $status_code = 0;       //û�и���:0, ����������ȼ�����ŵ�����1.��שʯ 2.������ 3.�¹�ע
    if($voteNum != $orig_voteNum){$status_code = 1;}
    if($lastCommentTime != $orig_lastCommentTime && \
       -1 != findKeywords($lastCommentContent, $commentKeywords)) // ������ʱ�����,����������������������"����"�ȹؼ���, �ͼ����.
    {
        $status_code = 2;
        // echo "������:".$commentKeywords[findKeywords($lastCommentContent, $commentKeywords)];
    }
    //if($followNum != $orig_followNum){$status_code = 3;}

    // ���޸ĺ��ֵ���µ��ļ�
    if(0 != $status_code)
    {
        $newValues = $voteNum."|".$lastCommentTime."|".$followNum;

        if(false == writeFileData($origValueFileName, $newValues))
        {
            // �����ֵд��ʧ��, �ͷ���$status_codeΪ0�� ��Ϊ����������ط�0, ��Ƭ���ᴥ��������
            // ��������һ������ʱ, ��Ȼ�ֻ᷵�ط�0(��Ϊֵδ����),����ɵ�Ƭ���ظ�ִ�ж�����
            $status_code = 0;
        }
    }

    // ���������ʱ��"last_check_time.log"
    writeFileData($lastCheckTime, date('y-m-d h:i:s',time()) );

    return $status_code;
}

/*
// ֻ�е����ݸ��»����ʱ���ŷ��أ�����һֱ�ȴ���ģ�ⳤ����(��������֧�ֳ����ӣ�ֻ����������ʽ��ʵ��)��
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