; **********************************************************************
; **  I2C control functions                     by Stephen C Cousins  **
; **********************************************************************
;
; FAKE version, all functions do a return. This is for easier debugging
; of caller code from SCW which doesn't have a step-over command
;

; Org for the code segment must be set by includer
            .CODE

; I2C bus open device
;   On entry: A = Device address (bit zero is read flag)
;             SCL = unknown, SDA = unknown
;   On exit:  If successfully A = 0 and Z flagged
;             If successfully A = Error and NZ flagged
;             SCL = lo, SDA = lo
;             HL IX IY preserved
; Possible errors:  1 = Bus jammed (not implemented)
I2C_Open:   XOR A
            RET


; I2C bus close device
;   On entry: SCL = unknown, SDA = unknown
;   On exit:  If successfully A=0 and Z flagged
;             If successfully A=Error and NZ flagged
;             SCL = hi, SDA = hi
;             HL IX IY preserved
; Possible errors:  1 = Bus jammed ??????????
I2C_Close:  JR I2C_Open

; I2C bus transmit frame (address or data)
;   On entry: A = Data byte, or
;                 Address byte (bit zero is read flag)
;             SCL = low, SDA = low
;   On exit:  If successful A=0 and Z flagged
;                SCL = lo, SDA = lo
;             If unsuccessful A=Error and NZ flagged
;                SCL = high, SDA = high, I2C closed
;             HL IX IY preserved
I2C_Write:  JR I2C_Open

; I2C bus receive frame (data)
;   On entry: SCL low, SDA low
;   On exit:  If successful A = data byte and Z flagged
;               SCL = low, SDA = low
;             If unsuccessul A = Error and NZ flagged
;               SCL = low, SDA = low ??? no failures supported
;             HL IX IY preserved
I2C_Read:   JR I2C_Open


; I2C bus start
;   On entry: SCL = unknown, SDA = unknown
;   On exit:  SCL = low, SDA = low
;             BC DE HL IX IY preserved
; First ensure SDA and SCL are high
I2C_Start:  RET


; I2C bus stop 
;   On entry: SCL = unknown, SDA = unknown
;   On exit:  SCL = high, SDA = high
;             BC DE HL IX IY preserved
; First ensure SDA and SCL are low
I2C_Stop:   RET

I2C_WrPort: RET

