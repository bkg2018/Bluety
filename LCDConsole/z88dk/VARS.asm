; Separated variables with own ORG
                ORG $ffff - (_DATA_End - _DATA_Start)
_DATA_Start:

I2C_RAMCPY: DEFB  0

; variables
LCD_RAMCOPY:                    .DS 80  ; 80 bytes for screen ram copy
LCDPOSX:    DEFB 0               ; X from 0 to 19
LCDPOSY:    DEFB 0               ; Y from 0 to 3
CURLINE:    DEFB 0               ; line number during work
; screen settings
LCDCONTROL: DEFB 0               ; control bits:
DEFC LCBIT_I2C      =0              ; 0 = I2C OFF
DEFC LCBIT_PARALLEL =1  ; 0 = PARALLEL OFF
DEFC LCBIT_WRAP     =2              ; 0 = No wrap
DEFC LCBIT_LCDONLY  =3  ; 1 = display ONLY on LCD
DEFC LCBIT_CURSOR   =4  ; 1 = cursor on
DEFC LCBIT_BLINK    =5  ; 1 = blinking cursor
DEFC LCBIT_BLOCK    =6  ; 1 = block cursor, 0 = underscore
DEFC LCBIT_NOLF     =7              ; 1 = do not interpret LF (0Ah)
LCDCONTROL2:                    DEFB 0  
DEFC LC2BIT_NOBS    =0  ; 1 = do not interpret BS (08h) 
LCDPORT:    DEFB 0               ; 0x0C  on SC126 I2C
I2CDEVICE:  DEFB 0               ; 0x27 for 8574T controllers
LCDCOLS:    DEFB 0               ; 20 columns
LCDROWS:    DEFB 0               ; 4 lines
LCD_INDEX:  DEFB 0,0,0,0         ; each line offset



DEFC _DATA_End = ASMPC
