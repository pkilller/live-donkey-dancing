/****************************************Copyright(c)**************************************************
**                              
**                                 ����¿(���߻�ȡָ��)
**										
**------------------------------------------------------------------------------------------------------		
** 
**    ˵����        ��ָ��web�ӿڻ�ȡָ��,����¿���Ƿ�Ҫ����(���Ƽ̵���).			    
**    WIFI������ 	WIFIģ������д��ָ����WIFI��������, �����Զ�����ָ�������� 
**    ����ʱ��:     2015-10-07				
**    �����ˣ�	    pkiller				
********************************************************************************************************/
#include "include.h"	
u8  str[4]={0};//���巢�ͻ���
u8  Status=0;

// �ӽӿڻ�ȡ360live��ָ������״̬.
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
    // ��ս��ջ�����
    RevCount            = 0;        
    memset(Buf, 0, sizeof(Buf));

	UART_SendData(szRequestHeader);
	Delay_500Us(10000);
    // �ȴ��������
    for(Index = 0; Index < MaxWaitS; Index++)
    {
        Delay_Ms(1000);
        // ��ͷ�������ݾ�������ɺ�, �����ȴ�
        DataBuf = strstr(Buf,"\r\n\r\n");
        if(DataBuf != 0)
        {
            DataBuf = DataBuf + 4;   // ����ͷ��(\r\n\r\n),����������
            if(DataBuf[0] != "\0")   // �������ݵĵ�1byte.
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

// �̵�����0.5��
void RelayTouch()
{
    Relay_Enable();
    Delay_Ms(500);
    Relay_Disable();
}

// ��˸LED
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
	HardWareInit();	// Ӳ����ʼ��
	Delay_500Us(20000);
	

    while(1)
    {
        _360LiveStatus = Get360LiveStatus();
    
        // ����0, ���� ��ʯ,����,��ע ֮һ��֮���и���
        if(_360LiveStatus > 0)
        {
            //��Ϣ����: ��"����¿"
            RelayTouch();           // ��"����¿"
            Delay_Ms(15*1000);      // ����15��    
            RelayTouch();           // �ر�"����¿"
        }
        else if(_360LiveStatus == 0)
        {
            //û�и���: �ȴ�3��, ͬʱLED��3��(��ʾ����ת����). �ٽ�����һ�μ��
            Led_Control(1);
            Delay_Ms(3*1000);   
            Led_Control(0);
        }
        else if(_360LiveStatus == -1)
        {
            //��ȡ360Live״̬ʱ����, ��˸һ���LED, ��չʾ������Ϣ.
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
