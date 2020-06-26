; **********************************************************************
; **  Alphanumeric LCD support                  by Stephen C Cousins  **
; **********************************************************************
;
;
; FAKE version, all functions do a return. This is for easier debugging
; of caller code from SCW which doesn't have a step-over command

#CODE _CODE
fLCD_Init:  RET


; Set or reset backlighting bit in A
;  On entry: A = 4-bit byte, (LCD_BLIGHT) = 0 to switch off, 1 to switch on
;  On exit: bit set or reset in A
SetA_Light: RET

; Write instruction to LCD
;   On entry: A = Instruction byte to be written
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
fLCD_Inst:  RET


; Write data to LCD
;   On entry: A = Data byte to be written
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
fLCD_Data:  RET


; Position cursor to specified location
;   On entry: A = Cursor position
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
fLCD_Pos:   RET


; Output text string to LCD
;   On entry: DE = Pointer to null terminated text string
;   On exit:  BC HL IX IY I AF' BC' DE' HL' preserved
fLCD_Str:   RET

; Output character string to LCD.
; The character string is taken at the adress following the CALL and ends with 0.
; The routine returns to the address after the string.
;   On entry: - (SP already points to the start of the string)
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
fLCD_Print: RET


; Define custom character
;   On entry: A = Character number (0 to 7)
;             DE = Pointer to character bitmap data
;   On exit:  A = Next character number
;             DE = Next location following bitmap
;             BC HL IX IY I AF' BC' DE' HL' preserved
; Character is 
fLCD_Def:   RET


; **********************************************************************
; **  Private functions
; **********************************************************************

; Write function to LCD
;   On entry: A = Function byte to be written
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
WrFn4bit:   RET


; Delay in milliseconds using SCM delay API
;   On entry: A = Number of milliseconds delay
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
LCDDelay1:  LD   A, 1           ;Delay by 1 ms
LCDDelay:   RET


; **********************************************************************
; **  Variables
; **********************************************************************

#DATA _DATA
LCD_BLIGHT: .DB  0  ; flag for backlighting
