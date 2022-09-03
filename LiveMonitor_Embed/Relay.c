#include "include.h"

// 启动继电器
void Relay_Enable()
{
    P3 = P3 & 0xFB;   // 1111 1011
}

// 关闭继电器
void Relay_Disable()
{
    P3 = P3 | 0x04;   // 0000 0100 
}