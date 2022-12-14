#include "include.h"

/*
*********************************************************************************************************
** 函数名称 ：Serial_Reg(u8 BaudRate)
** 函数功能 ：串口初始化 中断优先级最高
** 入口参数 ：BaudRate
** 出口参数 ：无
*********************************************************************************************************
*/
void Serial_Reg(u8 BaudRate)   //初始化UART
{	
	
	ES = 0;	 //关闭串口中断
	TR1 = 0;  //关闭定时器1
	TMOD |= 0x20; //定时器1工作在模式2
	TH1 = BaudRate;// 配置波特率
	TL1 = BaudRate;
	PCON =0x80;  //波特率倍增
	TR1 = 1;//开启T1定时器
	SCON = 0x50;// 串口工作在方式1  8位数据位  1位停止位  无校验位
	IP = 0x10;	// 串口优先中断
	ES = 1;	     //开启串口中断

}
/*
*********************************************************************************************************
** 函数名称 ：HardWareInit(void)
** 函数功能 ：硬件初始化                           
** 入口参数 ：无
** 出口参数 ：无
*********************************************************************************************************
*/
void HardWareInit(void)
{
	EA = 0;
	Serial_Reg(BR_115K2);   // 初始化通讯, 波特率bps BR_115K2
	EA = 1;
}			 

/*********************************************************************************************************
** 函数名称: void Delay_500Us(u16 i)
** 功能描述: 演示500us
** 入口参数: i:延时时间
** 出口参数: 无
********************************************************************************************************/
void Delay_500Us(u16 i)
{
  u16 j;
  	for(;i>0;i--)
  		for(j=110;j>0;j--);
}

void Delay_Ms(u16 i)
{
  u16 j;
  	for(;i>0;i--)
  		for(j=220;j>0;j--);
}

/*********************************************************************************************************
** 函数名称: void UART_SendData(u8 *data_buf)
** 功能描述: 串口发送数据
** 入口参数: data_buf:发送数据缓冲区首地址
** 出口参数: 无
********************************************************************************************************/
void UART_SendData(u8 *data_buf)
{
	u8 iSendCounter = 0;
	
	while(data_buf[iSendCounter] != '\0')
	{
		SBUF = data_buf[iSendCounter];	
		while(TI==0);
            TI=0;
		    iSendCounter++;
	}  
}

/*******************************************************************************
//      功能:       UART接收中断程序
//      输入参数:   无
//      输出参数:   无
//      返回值:     无
*******************************************************************************/
u8 xdata Buf[300];
u16 RevCount;
u8  NumCount;

void Serial_Interrupt() interrupt 4 using 3
{
		if(RI==1)// 接收数据
		{	    
	        RI=0; //软件清除接收中断
			if(RevCount < sizeof(Buf))
			{
    			Buf[RevCount]=SBUF; //取数据		
    		    RevCount++;
			}
						
		 }
}
