; **********************************************************************
; **  I2C LCD console demo  by Francis Pierot                         **
; **  Original I2C code by Stephen S. Cousins                         **
; **********************************************************************

; This program demonstrates the use of LCD Console on a SC126 I2C port
; with a 4x20 LCD display + I2C adapter.
;

#TARGET     Simulated_Z80       ; Determines hardware support included

            .PROC Z180          ;SC126 has a Z180
            .HEXBYTES 0x18      ; Intel format output size

            .DATA
            .ORG 0x9000         ; adjust this depending on code size

; starting address = top 32KB             
            .CODE   
            .ORG 0000h         
            JP PROGRAMSTART

            .ORG 0x8000         ; make sure we don't interfere with ROM

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
@DefLoop:   CALL fLCD_Def       ;Define custom character
            DJNZ @DefLoop       ;Repeat for each character

; Initialize console
            CALL LCDConsoleInit

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
; Small computer monitor API

; Delay by DE milliseconds (approx)
;   On entry: DE = Delay time in milliseconds
;   On exit:  IX IY preserved
API_Delay:  LD   C,0x0A
;$$         RST  0x30
            RET

; **********************************************************************
; **  Includes
; **********************************************************************
#INCLUDE    I2C_fake.asm
#INCLUDE    LCDConsole.asm



            .END





































