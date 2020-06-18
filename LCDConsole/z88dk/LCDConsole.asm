;---------------------------------------------------------------------------
; LCDConsole - set of functions for a console 
; LCDSetCur : set the cursor position
; LCDChar : outputs a character
; LCDClear : clears screen 
; LCDScrollUp : scrolls screen one line up and clears bottom line
;---------------------------------------------------------------------------

            ; INIT for classical 4x20 Hitachi protocol
LCDConsoleInit:                 
            PUSH AF
            PUSH HL
            ; program I2C port
            LD A,0x0C
            LD (LCDPORT),A
            ; clear cursor position 
            XOR A        
            LD (LCDPOSX),A
            LD (LCDPOSY),A
            ; set initial options : I2C and cursor
            SET LCBIT_I2C,A
            SET LCBIT_CURSOR,A
            LD (LCDCONTROL),A
            ; set device default address
            LD A,0x27
            LD (I2CDEVICE),A
            ; set 4x20 default size
            LD A,4
            LD (LCDROWS),A
            LD A,20
            LD (LCDCOLS),A
            ; initialize line start indices
            LD HL,LCD_INDEX     ; 0x00, 0x40, 0x00+20, 0x40+20
            LD (HL),0
            INC HL
            LD (HL),0x40
            INC HL
            LD A,(LCDCOLS)
            LD (HL),A
            INC HL
            ADD A,0x40
            LD (HL),A
            ; done with init
            ;CALL LCDClear
            POP HL
            POP AF
            RET

;            .CODE

;---------------------------------------------------------------------------
; LCDSetCur : set the cursor position
; IN: X position in B, Y position in C
;---------------------------------------------------------------------------
LCDSetCur:  PUSH AF
            LD A,(LCDCOLS)
            CP B                ; LCDCOLS < X : C set
            JR Z,XNOTOK        ; x = LCDCOLS : not ok
            JR NC,XOK          ; X < LCDCOLS -> ok
XNOTOK:     DEC A               ; LCDCOLS - 1
            LD B,A              ; X clamped at LCDCOLS-1
XOK:        LD A,B              ; reload X
            LD (LCDPOSX),A
            LD A,(LCDROWS)
            CP C                ; LCDROWS < Y : C set
            JR Z,YNOTOK        ; y = LCDROWS -> not ok
            JR NC,YOK          ; y < LCDROWS -> ok
YNOTOK:    DEC A
            LD C,A              ; y clamped at LCDROWS-1
YOK:       LD A,C              ; reload Y
            LD (LCDPOSY),A
            CALL LCDSetCurVar
            POP AF
            RET

; Set cursor from the X/Y variables
LCDSetCurVar:
            PUSH AF
            PUSH HL
            LD A,(LCDPOSY)
            ; add line number Y to index address
            LD HL,LCD_INDEX
            ADD A,L
            LD L,A
            ADC A,H
            SUB L
            LD H,A
            ; get the starting position for this line
            LD A,(HL)
            ; add column number
            LD HL,LCDPOSX
            ADD A,(HL)
            ; and send command
            CALL fLCD_Pos
            POP HL
            POP AF
            RET

;---------------------------------------------------------------------------
; LCDChar : outputs a character and update cursor position
; IN: character in A
; flags not preserved
;---------------------------------------------------------------------------
LCDChar:    PUSH HL             ; save
            ; have to test line feed ?
            LD HL,LCDCONTROL
            BIT LCBIT_NOLF,(HL)
            JR NZ,NOTLF
            ; do not ignore LF
            CP 0Ah
            JR NZ,NOTLF
            ; LF : go to crlf routine
            POP HL
            JP CRLF

NOTLF:     ; have to test backspace ?
            LD HL,LCDCONTROL2
            BIT LC2BIT_NOBS,(HL)
            JR NZ,NOTBS
            ; do not ignore BS
            CP 08h
            JR NZ,NOTBS
            ; back one char
            LD A,(LCDPOSX)
            OR A                ; zero?
            POP HL              ; restore HL now
            RET Z               ; yes: already at line beginning, finished
            ; update position and set cursor
            DEC A
            LD (LCDPOSX),A
            ; finish by setting cursor
            JP LCDSetCurVar 

NOTBS:     PUSH BC
            LD C,A              ; save char in C
            LD A,(LCDCOLS)
            LD B,A
            LD A,(LCDPOSX)
            CP B                ; xpos >= nbcols ?
            JR C,SENDCHAR      ; no, ok to send character
            ; at the end of line, check if we wrap
            CALL TESTWRAP
            JR NZ,DOWRAP 
            ; no wrap : finished
            POP BC
            POP HL
            RET

DOWRAP:    CALL CRLF

SENDCHAR:  LD A,C
            CALL fLCD_Data      ; send byte
            ; Compute RAM copy address
            LD HL,LCD_RAMCOPY   ; base address
            PUSH DE
            LD A,(LCDCOLS)      ; DE = width
            LD E,A
            LD D,0
            LD A,(LCDPOSY)      ; 0 to 3
ADDLINE:   OR A                ; A nul?
            JR Z,STORE
            ADD HL,DE
            DEC A
            JR ADDLINE
STORE:      LD A,(LCDPOSX)
            LD E,A
            ADD HL,DE           ; addresss ready
            LD (HL),C           ; store character
            ;update x pos
            LD A,(LCDPOSX)
            INC A
            LD (LCDPOSX),A

            POP DE
            POP BC
            POP HL                    
            RET

;---------------------------------------------------------------------------
; LCDString : Output character string to LCD. 
;---------------------------------------------------------------------------
; The character string is taken at the adress following the CALL and ends with 0.
; The routine returns to the address after the string.
;   On entry: - (SP already points to the start of the string)
;   On exit:  AF BC DE HL IX IY I AF' BC' DE' HL' preserved
LCDString:
            EX   (SP),HL        ;Save HL and get the return address
            PUSH  AF
            PUSH  BC
            PUSH    DE
NextCh:       LD   A,(HL)         ;Read next character 
            OR      A
            JR  Z,EndString          ;ends with a 0
            CALL    LCDChar     ;Write character to display
            INC  HL
            JR  NextCh
EndString:       INC  HL             ;jump over the ending 0
            POP     DE
            POP  BC
            POP  AF
            EX   (SP),HL        ;restore HL and new return address
            RET


;---------------------------------------------------------------------------
; LCDClear : Clears LCD screen 
;---------------------------------------------------------------------------
LCDClear:   PUSH AF
            PUSH BC
            PUSH HL
            XOR A               ; clear RAM screen copy
            LD B,80
            LD HL,LCD_RAMCOPY
ZERORAM:   LD (HL),A
            INC HL
            DJNZ ZERORAM
            ; now clear LCD
            ; Display Clear
            LD   A, 0b00000001  ;Control reg:  0  0  0  0  0  0  0  1
            CALL fLCD_Inst      ;Clear display
            ; cursor at 0,0
            LD BC,0
            CALL LCDSetCur
            POP HL
            POP DE
            POP AF

;---------------------------------------------------------------------------
; LCDScrollUp : scrolls the LCD one line up and clears bottom line
;---------------------------------------------------------------------------
LCDScrollUp:
#local    
            PUSH HL
            PUSH DE
            PUSH BC
            PUSH AF
            LD A,(LCDCOLS)
            LD L,0
            LD H,0              ; HL = 0
            LD E,A
            LD D,0              ; DE = 20
            LD A,(LCDROWS)      ; A = 4
            DEC A               ; A = 3
            LD B,A              ; B = 3
ADDWIDTH:  ADD HL,DE           ; HL = 20, 40, 60
            DJNZ ADDWIDTH
            PUSH HL             ; push 60
            LD DE,LCD_RAMCOPY   ; DE = source
            LD HL,LCD_RAMCOPY   ; HL = source
            LD A,(LCDCOLS)      ; A = 20
            LD C,A
            LD B,0              ; BC = 20
            ADD HL,BC           ; HL = source + 20
            POP BC              ; BC = 60
            LDIR                ; transfer the 60 bytes
            LD A,(LCDCOLS)
            LD B,A              ; B = 20
            LD A,' '            ; space character
            PUSH DE
            POP HL              ; HL = source+60
NEXTCHAR:   LD (HL),A           ; store a space
            INC HL
            DJNZ NEXTCHAR
            ; ram copy scrolled and last line is full space
            CALL FULLCOPY       ; and send full content to LCD
            POP AF
            POP BC
            POP DE
            POP HL
            RET
#endlocal

;---------------------------------------------------------------------------
; Utility routines
;---------------------------------------------------------------------------
; Copy ram image to LCD memory
FULLCOPY:   
#local
            PUSH HL
            PUSH BC
            PUSH AF
            LD BC,0
            CALL LCDSetCur
            XOR A
            LD (CURLINE),A
            LD HL,LCD_RAMCOPY
            LD A,(LCDROWS)
            LD B,A              ; B = number of lines

NEXTLINE:  PUSH BC             ; start current line for 20 characters
            LD A,(LCDCOLS)
            LD B,A              ; B = number of cols

NEXTCHAR:  LD A,(HL)           ; get current character
            CALL LCDChar        ; send it to LCD
            INC HL              ; advance source
            DJNZ NEXTCHAR      ; go next character on line
            ; set cursor to next line
            LD A,(CURLINE)
            INC A
            LD (CURLINE),A      ; next line number
            LD C,A
            CALL LCDSetCur      ; set to Y,0
            POP BC              ; get back current line number
            DJNZ NEXTLINE
            ;we're done
            POP AF
            POP BC
            POP HL
            RET
#endlocal

; Test wrap control bit, sets Z if no wrapping
TESTWRAP:   PUSH HL
            LD HL,LCDCONTROL
            BIT LCBIT_WRAP,(HL)
            POP HL
            RET

; back to beginning of line
CRLF:       PUSH AF
            PUSH HL
            XOR A
            LD (LCDPOSX),A      ; 0 => X
            LD HL,LCDPOSY       ; Y = 0-3
            LD A,(LCDROWS)      ; 3
            DEC A               ; 2
            CP (HL)             ; 2 >= Y? => NC
            JR NC,INCRY        ; ok to increment Y
            ; lock Y at 3, and scroll up
            POP HL
            POP AF
            JP LCDScrollUp
INCRY:     INC (HL)
            POP HL
            POP AF
            RET                    
            
            INCLUDE    "I2C.asm"
            INCLUDE    "Alphanumeric_LCD_I2C.asm"
            INCLUDE     "VARS.asm"

           


