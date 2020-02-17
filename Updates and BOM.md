# Back Panel

- taille = front panel

* prise alimentation fem 5.6mm : trou diam  7.5 mm (-> SC126 J2)
* reset button                 : trou diam 11.6 mm (-> SC126 P8)
* switch scm/wbw               : trou rect 9.2 x 3.8 mm,  trous diam 2 mm (2 fixations)  (-> SC126 P9)
- switch write protect A / B   : 2 x trou rect 9.2 x 3.8 mm, trous diam 2 mm (2 fxations)  (-> SC126 JP1 / JP2)
- hdmi fem                     : trou diam 22 mm ( pi zt)
- usb fem                      : trou rect 13.2 x 5.6 mm   (-> pi zt)
- 2 hdr fem 6P (serial A / B)  : 

# Modifications

## I/O PANEL

* rd et wr inversés sur le header P1 
* ajouter un repère au silk pour les réseaux de résistances
* décaler les deux vis de gauche de fixation    de 2 mm vers la droite
* élargir I/O panel, attention les input/output headers ne doivent pas dépasser le bord du LCD panel
* (abandonné) écarter les switchs, on ne peut pas les visser. voir si on peut écarter suffisamment pour les capuchons caoutchouc
* les dip-switch ont les bits inversés (0 à gauche)
* mentions copyright en silk plutôt que cuivre
* mention copyright sur front panel (au dos seulement ?)
* élargir et écarter pins des switchs
* inscire mention des valeurs ds résistances et condos
* C5 à passer en back plane
* FP1 à FP6 à renommer en FH1 à FH4 
* RP1 à RP6 à renommer en RN1 à RN6 
* version -> 1.1

## Bus Daughter
* C1 renommé en C9
* P1 à renommer P3
* inverser les deux rangées des headers sinon il faut croiser le cable
- headers angle droit au lieu de straight


## Front panel


* élargir fenêtre LCD
* décaler les deux vis de gauche de fixation io panel de 2 mm vers la droite

## Status Panel

- ajouter un point au silk pour les réseaux de résistances
- status panel : indiquer + - leds
* status panel: RN1 à renommer en RN7


# Mounting order

## I/O Panel

1. Front IC Sockets
	+ 2 * 14 pins: SW10, SW21
	+ 2 * 2 pins FH5/FH6
	- (? 32 * 2 pins: leds)
- Back IC Sockets
	+ 6 x 10 pins: U1 U2 U3 U5 U6 U7
	+ 1 x 14 pins: U4
	+ Resistor networks: RN1-RN6 (can be put on front)
- Back components
	+ 6 x 100 nF Capacitors: C1-C4, C6 C7
	+ 1 x 100 uF Capacitor C5
	+ 1 x 2*12 double male header P1 
- Front 16 x 3-pos switches
- Front 4  x 2-pos switches
- Front 32 * LEDs 
	- put leds on the io panel front
	- use 2 screws to fix the io panel to the front panel
	- reverse the panels so the leds fall in their corresponding front panel hole
	- adjust any LED position in their hole
	- solder LEDs on the back side of the io panel

# Status panel

1. LEDs (front)
- Resistor Network (back)
- Header (back)


# BOM (Bill Of Materials)

## Furnitures

- anti static foam
- anti static bags
- bags

## I/O Panel

### Switches

- 16 switchs on-off-on 3 pins (SW1-8/SW12-19)
- 4  switchs on-on / on-off 3 pins (SW9/SW11/SW20/SW22)
- 2  8-bit dip-switchs (SW10/SW21)

### LED

- 16 greeen led 3mm (LED1-8/LED17-24)
- 16 yellow led 3mm (LED9-16/LED25-32)
- 16 2-pin female headers

### Passive Components

- 1  100uF capacitor (C5)
- 7  100nF capacitor (C1-4, C6-7)
- 2  10K 9 pins resistor network (RP1/RP4)
- 4  470R 9 pins resistor network (RP2-3/RP5-6)

### Integrated Circuits

- 2  74HCT273N (U2/U6)
- 2  74HCT688 (U1/U5)
- 2  74HCT245N (U3/U7)
- 1  74HCT32 (U4)
- 7  20-pins DIP socket
- 1  14-pins DIP socket

### Headers

- 4  8-pins female header (FP1-4)
- 2  2-pins female header (FP5-6)
- 1  2x12 male header (P1)


## Status panel

### LEDs

- 8  blue led 3mm (LED33-40)
- 1  470R 9 pins resistor network (RN1)
- 9 pins male header
- 9 wires cable (female/female) -> SC126 led pins  
- front panel screwery                                                                                                                                                                                                                              

## Bus Daughter

- 1  2x12 male double header right angle (P3)
- 1  1x40 male single header right-angle (P2)
- 1  100nF capacitor (C9)
- 2  F/F 12 wires cable (P3->io panel header)

## LCD Panel (4x20)

Either:

- Ready to use LCD2004 with 4 bits interface
- 7 wires connector
- SC129 card

or:

- ready to use LCD2004 with I2C adapter
- - 4 wires connector

## Front panel

- 20mm diameter on/off button with LED
- 5 wires cable
- 4 case screws
- 4 fixations + 4 screws for io panel
- 4 fixations + 4 screews for status panel
- 4 fixations + 4 screws for lcd panel


