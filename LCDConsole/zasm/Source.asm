; **********************************************************************
; **  I2C LCD console demo  by Francis Pierot                         **
; **  Original I2C code by Stephen S. Cousins                         **
; **********************************************************************

; This program demonstrates the use of LCD Console on a SC126 I2C port
; with a 4x20 LCD display + I2C adapter.
;

;#TARGET     Simulated_Z80       ; Determines hardware support included

;            .PROC Z180          ;SC126 has a Z180
;           .HEXBYTES 0x18      ; Intel format output size

;            .DATA
;            .ORG 0x9000         ; adjust this depending on code size

; ZASM Syntax. ZASM HEX output seems to concatenate segments from 0000 whatever
; their ORG is, so a loader is needed to put code segment at the destination address
; at start. using BIN target doesn't seem to make a difference from ROM. It's disturbing
; because listing file shows the correct addresses.
;
; xxx_size labels are generated by ZASM from the relevant segment size after pass 2. Notice these
; automatic labels are case dependent.
;
; three segments are defined here: _BOOT will start at $0000 and load code into $8000 area and 
; jump to it, _CODE will hold all the code, and _DATA will be the space for variables which is 
; put at end of 64K with stack pointer moved before this variables space.
;
#TARGET BIN        
#CODE       _BOOT,0000h
            ORG $0000
            ; load code to actual address
            LD DE,_CODE
            LD HL,_BOOT_size
            LD BC,_CODE_size
            LDIR
            ; adjust SP before variables storage
            LD SP,$ffff-_DATA_size
            ; now we can jump to code address
            JP PROGRAMSTART
;'stack_top' is a label for DeZog debugger in Visual Code            
stack_top:  .EQU $ffff-_DATA_size

; DATA segment can be put anywhere in RAM, here we put it at the end of 64K.
#DATA       _DATA,$ffff-_DATA_size
            ORG $ffff-_DATA_size

#CODE       _CODE,$8000,*
            ORG $8000

; Optional code, insert functions or not
USE_I2CREAD .EQU 0              ;optional I2C reading
USE_LCDPRINT .EQU 0             ;optional I2C string print out of console
; Constants needed by the I2C support module
I2C_PORT:   .EQU 0x0C           ;Host I2C port address
I2C_SDA_WR: .EQU 7              ;Host I2C write SDA bit number
I2C_SCL_WR: .EQU 0              ;Host I2C write SCL bit number
I2C_SDA_RD: .EQU 7              ;Host I2C read SDA bit number
I2C_ADDR:   .EQU 0x27 << 1      ;I2C device default address (8574T with A0=A1=A2=1), bit 0 = 0 for R/W

; LCD constants required by LCD support module
kLCDBitRS:  .EQU 0              ;data bit for LCD RS signal
kLCDBitRW:  .EQU 1              ;data bit for LCD R/W signal
kLCDBitE:   .EQU 2              ;data bit for LCD E signal
kLCDBitBL:  .EQU 3              ;data bit for backlighting
kLCDWidth:  .EQU 20             ;Width in characters


PROGRAMSTART:

; Initialise I2C and alphanumeric LCD module
            CALL fLCD_Init      ;Initialise LCD module

; Define custom character(s)
            LD   A, 0           ;First character to define (0 to 7)
            LD   DE, BitMaps    ;Pointer to start of bitmap data
            LD   B, 2           ;Number of characters to define
#local
DefLoop:    CALL fLCD_Def       ;Define custom character
            DJNZ DefLoop       ;Repeat for each character
#endlocal
; Initialize console
            CALL LCDConsoleInit

; Clear display (already done in console init, this is for example only)
            CALL LCDClear            

; Display text on first line
            CALL LCDString
            .DB "Hello, world!",0Ah,0

; Display text on second line
            CALL LCDString
            .DB "I'm an SCZ180/126",0Ah,0

; Display custom character 0 on the right 1st line
            LD BC,0013h
            CALL LCDSetCur
            LD A,0
            CALL LCDChar

; Display custom character 1 on the right 2nd line
            LD BC,0113h
            CALL LCDSetCur
            LD A,1
            CALL LCDChar

            JP $                ; finished

; Custom characters 5 pixels wide by 8 pixels high
; Up to 8 custom characters can be defined
BitMaps:
; Character 0x00 = Battery icon
            .DB  0b01110
            .DB  0b11011
            .DB  0b10001
            .DB  0b10001
            .DB  0b11111
            .DB  0b11111
            .DB  0b11111
            .DB  0b11111
; Character 0x01 = Bluetooth icon
            .DB  0b00110
            .DB  0b10101
            .DB  0b01110
            .DB  0b00100
            .DB  0b01110
            .DB  0b10101
            .DB  0b00110
            .DB  0b00000



; **********************************************************************
; **  Includes
; **********************************************************************
            INCLUDE    "I2C.asm"
            INCLUDE    "LCDConsole.asm"

            .END





































