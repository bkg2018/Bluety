# BOM

## Circuits imprimés

* façade avant
* façade arrière
* bus daughter
* blink'n switch

## Accessoires façade avant

* bouton on/off 
* cable 5 fils custom on/off + led
* LCD 4x20 I2C display
- - cable dupont 4 fils F/M 10 ou 20 cm (I2C=>lcd)
* 4 Vis+boulon+support 8mm pour lcd=>façade

## accessoires façade arrière

* 1 bouton reset
* - cable custom 2 fils reset (btn->dupont F) 
* 1 connecteur 5V
* - cable custom 2 fils alim (btn->dupont F)
* 3 micro switchs
* 6 vis+boulon M2x8
* - 3 cables 3 fils pour micro switchs (btn->dupont F)
* 1 connecteur USB F
* 1 connecteur micro USB à souder
* - cable USB 4 fils (connecteur->micro USB)
* 1 connecteur HDMI
* 2 vis M3x8 + boulon
* - cable HDMI->micro hdmi

pas fourni : clé allen pour les vis M3x8

## Blink'n Switch

### Accessoires

* 5 vis+boulon+support 12mm pour fixation sur façade avant
* 8 LED 3mm bleues ou translucides/bleue
* 16 LED 3mm jaunes ou translucides/jaune
* 16 LED 3mm vertes ou translucides/vertes
* 16 interrupteurs on-off-on (bleu foncé)
* 4 interrupteurs on-off ou on-on (bleu clair)
* 2 DIP-Switchs bleus 8P
* 4 supports CI DIP16 (pour les dip switch)
* 4 headers F 8P 11mm bleus
* 2 headers F 2P 11mm noirs
* 8 supports CI 20P
* 2 supports CI 14P
* 1 connecteur F 2x12P pour cable plat

### Composants

* 5 Réseaux résistances 470R 9P (A471J)
* 2 Réseaux résistances 10K 9P (A103J)
* 1 condensateur 100uF électrolytique
* 9 condensateurs 100nF céramique
* 3 74HCT273N
* 3 74HCT688
* 2 74HCT245N
* 2 74HCT32

## Bus Daughter

### Accessoires

* 1 header 40P angle droit
* 1 connecteur F 2x12P pour cable plat
* - cable plat 24P + 2 connecteurs M 2*12

### Composants

* 1 condensateurs 100nF céramique

# Mounting order

## Blink'n Switch

The Blink'n Switch I/O panel must be soldered before it is mounted on case front panel. However, a temporary mounting is necessary to make sure the headers and LEDs on the front panel will have the right legs length. To do this mounting you will need two (2) 12mm fixations with their screws.

### Front IC Sockets

First solder the IC socket on the front of Blink'n Switch, they will host the DIP switch later

- 2 * 14 pins sockets : SW10, SW21

### Back IC Sockets
- 6 x 10 pins: U1 U2 U3 U5 U6 U7
- 1 x 14 pins: U4
- Resistor networks: RN1-RN6 (can be put on front)

### Back components
- 6 x 100 nF Capacitors: C1-C4, C6 C7
- 1 x 100 uF Capacitor C5
- 1 x 2*12 double male header P1 
- Front 16 x 3-pos switches
- Front 4  x 2-pos switches
- Front 40 * LEDs 
	- put leds on the io panel, front side. make sure you use the right colors. Take care of polarity : the longest leg goes in the + hole.
	- while keeping the IO board with front up so the leds don't fall, use 2 screws and 12mm supports at opposite corners to fix the io panel to the front panel
	- rapidly reverse the panels upside down so the leds fall in their corresponding front panel hole
	- adjust any LED position in their hole
	- solder the + leg of each LED - DO NOT SOLDER THE - LEG YET
	- turn the panels upside down to check each LED position. Adjustany needed LED by heating its + leg and adjusting the LED position.
	- once every LED is correctly in place, solder the - legs

If you have 40 2-pin female headers 

# Status panel

1. LEDs (front)
- Resistor Network (back)
- Header (back)

# Back Panel

- taille = front panel

* prise alimentation fem 5.6mm : trou diam  7.5 mm (-> SC126 J2)
* reset button                 : trou diam 11.6 mm (-> SC126 P8)
* switch scm/wbw               : trou rect 9.2 x 3.8 mm,  trous diam 2 mm (2 fixations)  (-> SC126 P9)
- switch write protect A / B   : 2 x trou rect 9.2 x 3.8 mm, trous diam 2 mm (2 fxations)  (-> SC126 JP1 / JP2)
- hdmi fem                     : trou diam 22 mm ( pi zt)
- usb fem                      : trou rect 13.2 x 5.6 mm   (-> pi zt)


# BOM (Bill Of Materials)

## Furnitures

- anti static foam
- anti static bags
- bags

## Bus Daughter

- 1  2x12 male double header right angle (P3)
- 1  1x40 male single header right-angle (P2)
- 1  100nF capacitor (C9)
- 2  F/F 12 wires cable (P3->io panel header)

## Front panel

- 20mm diameter on/off button with LED
- 5 wires cable -> on/off button
- 5x 10mm fixation/screws/nuts [Banggood Reference](https://www.banggood.com/Suleve-M3NH1-M3-Nylon-Screw-Black-Hex-Screw-Nut-Nylon-PCB-Standoff-Assortment-Kit-300pcs-p-984310.html?rmmds=detail-top-buytogether-auto&cur_warehouse=CN) to mount I/O Panel
- 4x 6mm fixations/screws/nuts to mount lcd panel


## Back panel

- HDMI connector
- USB connector
- 3 micro switches on/on
- power connector
- 6 screws 2mm + nut, L 6mm 
- 2 screws 3mm + nut, L 8mm

## LCD Panel (4x20)

Either:

- Ready to use LCD2004 with 4 bits interface
- 7 wires connector
- SC129 card

or:

- Ready to use LCD2004 with I2C adapter
- 4 wires connector

