#include "include.h"

// �����̵���
void Relay_Enable()
{
    P3 = P3 & 0xFB;   // 1111 1011
}

// �رռ̵���
void Relay_Disable()
{
    P3 = P3 | 0x04;   // 0000 0100 
}