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

; Z88DK/z80asm Syntax. Better for DeZog use but not a lot of facilities.
; - only one ORG allowed
; - no code/data sections with separate orgs
; - no local labels
; - no forward references
; - no 'h' suffix for hexa
;
; Syntax changes:
; name .EQU val  ->  DEFC name=val
; .DB -> DEFB
;
            ORG $8000

; Optional code, insert functions or not
DEFC USE_I2CREAD    = 0              ;optional I2C reading
DEFC USE_LCDPRINT   = 0             ;optional I2C string print out of console
; Constants needed by the I2C support module
DEFC I2C_PORT       = 0x0C           ;Host I2C port address
DEFC I2C_SDA_WR     =  7              ;Host I2C write SDA bit number
DEFC I2C_SCL_WR     =  0              ;Host I2C write SCL bit number
DEFC I2C_SDA_RD     =  7              ;Host I2C read SDA bit number
DEFC I2C_ADDR       = 0x27 << 1      ;I2C device default address (8574T with A0=A1=A2=1), bit 0 = 0 for R/W

; LCD constants required by LCD support module
DEFC kLCDBitRS      = 0              ;data bit for LCD RS signal
DEFC kLCDBitRW      = 1              ;data bit for LCD R/W signal
DEFC kLCDBitE       = 2              ;data bit for LCD E signal
DEFC kLCDBitBL      = 3              ;data bit for backlighting
DEFC kLCDWidth      = 20             ;Width in characters


PROGRAMSTART:

; Initialise I2C and alphanumeric LCD module
            CALL fLCD_Init      ;Initialise LCD module

; Define custom character(s)
            LD   A, 0           ;First character to define (0 to 7)
            LD   DE, BitMaps    ;Pointer to start of bitmap data
            LD   B, 2           ;Number of characters to define
DefLoop:    CALL fLCD_Def       ;Define custom character
            DJNZ DefLoop       ;Repeat for each character
; Initialize console
            CALL LCDConsoleInit

; Clear display (already done in console init, this is for example only)
            CALL LCDClear            

; Display text on first line
            CALL LCDString
            DEFB "Hello, world!",0Ah,0

; Display text on second line
            CALL LCDString
            DEFB "I'm an SCZ180/126",0Ah,0

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
            DEFB  @01110
            DEFB  @11011
            DEFB  @10001
            DEFB  @10001
            DEFB  @11111
            DEFB  @11111
            DEFB  @11111
            DEFB  @11111
; Character 0x01 = Bluetooth icon
            DEFB  @00110
            DEFB  @10101
            DEFB  @01110
            DEFB  @00100
            DEFB  @01110
            DEFB  @10101
            DEFB  @00110
            DEFB  @00000



; **********************************************************************
; **  Includes
; **********************************************************************
            INCLUDE    "LCDConsole.asm"

            .END





































