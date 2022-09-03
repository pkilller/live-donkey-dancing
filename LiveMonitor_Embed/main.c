/****************************************Copyright(c)**************************************************
**                              
**                                 跳舞驴(在线获取指令)
**										
**------------------------------------------------------------------------------------------------------		
** 
**    说明：        从指定web接口获取指令,决定驴子是否要跳舞(控制继电器).			    
**    WIFI描述： 	WIFI模块内已写入指定的WIFI名称密码, 并且自动连接指定服务器 
**    创建时间:     2015-10-07				
**    创建人：	    pkiller				
********************************************************************************************************/
#include "include.h"	
u8  str[4]={0};//定义发送缓存
u8  Status=0;

// 从接口获取360live的指定房间状态.
int Get360LiveStatus()
{
	u8  xdata szRequestHeader[300] = "GET /live_monitor/360lives/get_cur_info.php HTTP/1.1\r\n"  \
                      "Host: www.sui-yun.com\r\n"   \
                      "Connection: keep-alive\r\n"  \
                      "\r\n";
                      /*
    u8  xdata szResponse[300] = "HTTP/1.1 200 OK" \
                                "Content-Length: 1\r\n" \
                                "Content-Type: text/html\r\n" \
                                "Connection: close\r\n" \
                                "\r\n" \
                                "0"; */
    int ret = -1;
    char *DataBuf       = 0;
    int MaxWaitS        = 10;
    int Index           = 0;
    bool RecvSucceed    = false;
    // 清空接收缓存区
    RevCount            = 0;        
    memset(Buf, 0, sizeof(Buf));

	UART_SendData(szRequestHeader);
	Delay_500Us(10000);
    // 等待接收完毕
    for(Index = 0; Index < MaxWaitS; Index++)
    {
        Delay_Ms(1000);
        // 当头部和数据均接收完成后, 跳出等待
        DataBuf = strstr(Buf,"\r\n\r\n");
        if(DataBuf != 0)
        {
            DataBuf = DataBuf + 4;   // 跳过头部(\r\n\r\n),到数据正文
            if(DataBuf[0] != "\0")   // 主体数据的第1byte.
            {
                RecvSucceed = true;
                break;
            }
        }
    }

    if (RecvSucceed == true)
    {
		if(DataBuf[0]=='0')
		{
            ret = 0; 
		}
		else if(DataBuf[0]=='1')
		{
            ret = 1;
		}
        else if(DataBuf[0]=='2')
		{
            ret = 2;
		}
        else if(DataBuf[0]=='3')
		{
            ret = 3;
		}
        else if(DataBuf[0]=='-' && DataBuf[1]=='1')   //-1
		{
            ret = -1;
		}
    }
    return ret;
}

// 继电器打开0.5秒
void RelayTouch()
{
    Relay_Enable();
    Delay_Ms(500);
    Relay_Disable();
}

// 闪烁LED
void FlickLED()
{
    int nIndex = 0;
    for(nIndex = 0; nIndex < 10; nIndex++)
    {
        Led_Control(1);
        Delay_500Us(1000);
        Led_Control(0);
        Delay_500Us(1000);
    }
}

void main()
{
    int _360LiveStatus   = 0;
    int nIndex          = 0;
	HardWareInit();	// 硬件初始化
	Delay_500Us(20000);
	

    while(1)
    {
        _360LiveStatus = Get360LiveStatus();
    
        // 大于0, 表明 钻石,评论,关注 之一或之多有更新
        if(_360LiveStatus > 0)
        {
            //信息更新: 打开"跳舞驴"
            RelayTouch();           // 打开"跳舞驴"
            Delay_Ms(15*1000);      // 跳舞15秒    
            RelayTouch();           // 关闭"跳舞驴"
        }
        else if(_360LiveStatus == 0)
        {
            //没有更新: 等待3秒, 同时LED亮3秒(表示运行转正常). 再进行下一次检查
            Led_Control(1);
            Delay_Ms(3*1000);   
            Led_Control(0);
        }
        else if(_360LiveStatus == -1)
        {
            //读取360Live状态时出错, 闪烁一会儿LED, 以展示错误信息.
            FlickLED();
        }
    }

    /*
    if(_360LiveStatus == -1)
    {	
        Led_Control(1);
    }
    else
    {
        for(nIndex = 0; nIndex < 10; nIndex++)
        {
            Led_Control(1);
            Delay_500Us(1000);
            Led_Control(0);
            Delay_500Us(1000);
        }
    }*/
            	   
    while(1){Delay_500Us(100);};
}
