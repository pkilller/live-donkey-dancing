#include "include.h"

void Led_Control(u8 Led_Status)
{
	if(Led_Status==1)
	GPIO_LED = 0x00;	
	else
	GPIO_LED = 0xFF;
}