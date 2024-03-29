# List of Components<A id="a6"></A>

This document will list all the components you must gather before assembling.

First, a very important notice.

<TABLE><TR><TD><img src="Pictures/attention.png" width="300px" /></TD><TD><B>Do not touch the integrated circuits and the LCD screen without wearing an antistatic wristwrath linked to some ground.</B>. Preferably, leave them in their antistatic bag until final and front panel assembly to limit the risk of a static discharge which could damage them seriously. In any case, never place them before doing the electric checkings.</TD></TR></TABLE>

<TABLE><TR><TD><img src="Pictures/thisway.png" alt="Verification" width="150px" /></TD><TD>During assembly, you will be informed to proceed with a specific checking by this panel. Do not oversee these recommendations, they will guarantee a perfect assembly where proceeding is not obvious.</TD></TR></TABLE>

## Case<A id="a7"></A>

Bluety is designed for a blue metal case which can be found on most commercial sites with an electronics section: AliExpress, Banggood, Amazon, eBay to name a few. In their search input field, enter the following: **"Blue Metal Electronic Enclosure"** and choose the case with following dimensions:  **250 x 190 x 110** (mm) in results.

<TABLE><TR><TD><img src="Pictures/attention.png" width="100px" /></TD><TD>There are other similar cases
with inferior dimensions which won't accept Bluety boards size, so make sure you choose the right dimensions 250x190x110.</TD></TR></TABLE>

| <img src="Pictures/00-AE.png" alt="AliExpress" style="zoom:50%;" /> | <img src="Pictures/00-BG.png" alt="BangGood" style="zoom:50%;" /> |
| ------------------------------------------------------------------- | ----------------------------------------------------------------- |
| <img src="Pictures/00-AZ.png" alt="Amazon" style="zoom:50%;" />     | <img src="Pictures/00-EB.png" alt="eBay" style="zoom:50%;" />     |

The cost is about 20 to 40 euros or dollars shipping included.

The plastic frames holding the front and back panels may have their corners slightly damaged during shipping. I had this problem with 3 cases I ordered.

## Printed boards<A id="a8"></A>

To build Bluety you will need its four printed boards, which you can build using the files available at the adresses from the following table. I did mine at JLCPCB which has reasonable costs, although it forces you to build at least a 10 parts batch. You may resell your bonus boards if you like.

| Printed Board Name       | Purpose                                  | Web Address for files |
|--------------------------|------------------------------------------|------------------------|
| Bus Daughter             | Connects the I/O board to the RC2014 bus | https://oshwlab.com/bkg2018/front-panel-bus-daughter
| Blink'n Switch           | 2 ports I/O board with LEDs and Switches | https://oshwlab.com/bkg2018/blink-n-switch-front-panel-for-rc2014
| Front Panel              | Front panel for the case                 | https://oshwlab.com/bkg2018/blink-n-switch-front-panel-for-rc2014_copy
| Back Panel               | Back panel for the case                  | https://oshwlab.com/bkg2018/bluety-back-panel
| (optional) FP113-Tiny    | SC126 bus expansion for Bluety           | https://oshwlab.com/bkg2018/sc113-t

<TABLE><TR><TD><img src="Pictures/attention.png" width="180px" /></TD><TD>Make sure. you protect both front and
back panels from scratching, projections, burning during soldering. These are the visible faces of Bluety
so keep them apart until the final assembly. There is no soldering at all on the front and back panels.</TD></TR></TABLE>

Both panels can arrive slightly curved but this disappears once fixed to the case.

| Number | Board          |                                                                                     |
| -------| -------------- | ----------------------------------------------------------------------------------: |
| 1      | Bus Daughter   | <img src="Pictures/012-busdaughter.jpg" alt="Bus Daughter" style="zoom: 33%;" />    |
| 1      | Blink'n Switch | <img src="Pictures/020-blinknswitch.jpg" alt="Blink'n Switch" style="zoom: 33%;" /> |
| 1      | Front panel    | <img src="Pictures/021-frontpanel.jpg" alt="Front Panel" style="zoom: 33%;" />      |
| 1      | Back panel     | <img src="Pictures/021B-backpanel.jpg" alt="Back panel" style="zoom: 33%;" />       |

## Components<A id="a9"></A>

Here is the list for passive and active electronic components.

| Number | Components                                            |                                                                          |
| ------ | ----------------------------------------------------- | -----------------------------------------------------------------------: |
| 40     | 8 blue 3mm LED<br />16 green 3mm LED<br />16 yellow 3mm LED<br /><br />Notice: you can choose the LED colors when ordering. | <img src="Pictures/026-LEDs.jpg" alt="LEDs" style="zoom: 33%;" /> |
| 11     | 100nF Ceramic capacitors (marked 104)                 | <img src="Pictures/013-Capa100nF.jpg" alt="100 nF (or 0.1 uF)" style="zoom: 33%;" /> |
| 1      | 100uF electrolytic capacitor                          | <img src="Pictures/034-capa100uF.jpg" alt="100 uF" style="zoom: 33%;" /> |
| 3      | 10 KOhms Resistor network (marked 103)                | <img src="Pictures/042A.jpg" alt="10 Kohm" style="zoom: 33%;" />         |
| 5      | 470 Ohms Resistor network (marked 471)                | <img src="Pictures/043A.jpg" alt="470 Ohm" style="zoom: 33%;" />         |
| 10     | Integrated circuits :<br />- 3x 74HCT273N<br />- 2x 74HCT245N<br />- 3x 74HCT688<br />- 2x 74HCT32 | <img src="Pictures/037-ics.jpg" alt="Integrated circuits" style="zoom: 33%;" /> |
| 1      | Backlit 4x20 LCD screen with I2C adapter              | <img src="Pictures/038-LCD.jpg" alt="LCD display" style="zoom: 33%;" />    |

## Connectors and sockets<A id="a10"></A>

Here is the list for connecting components.

| Number | Connectors and circuits sockets |                                                                                            |
| ------ | -------------------------------------------- | -----------------------------------------------------------------------------------------: |
| 1      | 40 pins straight header                      | <img src="Pictures/014-header40P.jpg" alt="40 pins header" style="zoom: 33%;" />           |
| 1      | 2x12 pins male right-angled header           | <img src="Pictures/015-header2x12P.jpg" alt="2x12 pins headers" style="zoom: 33%;" />       |
| 4      | 16 pins IC socket                            | <img src="Pictures/023-support16.jpg" alt="16 pins IC socket" style="zoom: 33%;" /> |
| 2      | 14 pins IC socket                            | <img src="Pictures/024-support14.jpg" alt="14 pins IC socket" style="zoom: 33%;" /> |
| 8      | 20 pins IC socket                            | <img src="Pictures/025-support20.jpg" alt="20 pins IC socket" style="zoom: 33%;" /> |
| 2      | 2 pins female header with 11mm legs          | <img src="Pictures/030-h2P.jpg" alt="2 pins headers" style="zoom: 33%;" />                 |
| 4      | 8 pins female header with 11mm legs          | <img src="Pictures/031-h8P.jpg" alt="8 pins headers" style="zoom: 33%;" />                 |
| 1      | 2x12 pins male straight header               | <img src="Pictures/032-h2x12P.jpg" alt="2x12 pins headers" style="zoom: 33%;" />            |
| 1      | power connector                              | <img src="Pictures/039-power.jpg" alt="power connector" style="zoom: 33%;" />      |
| 1      | HDMI connector                               | <img src="Pictures/040-hdmi.jpg" alt="HDMI connector" style="zoom: 33%;" />               |

## Switches<A id="a11"></A>

Bluety uses four 2-positions switches which control powering of the 4 LEDs banks and sixteen 3-positions switches which control
the input port bits. To avoid mismatching, you can order different colors but when assembling, it will be a good idea to
double check that switches types are as expected before soldering them. It must be noticed that the switches are not
rigorously symetrical and must be all oriented the same way to get a nice alignment: this will be explained in assembling
instructions. When ordering, make sure you get the right dimensions as not all manufacturers use the same.

| Number | Interrupteurs                        |                                                                                            |
| ------ | -----------------------------------  | -----------------------------------------------------------------------------------------: |
| 4      | ON/ON or ON/OFF 2-pos switches       | <img src="Pictures/027-ONON.jpg" alt="ON/ON" style="zoom: 33%;" />                         |
| 16     | ON-OFF-ON 3-pos switches             | <img src="Pictures/028-ONOFFFON.jpg" alt="ON/OFF/ON" style="zoom: 33%;" />                 |
| 2      | 8 positions dipswitch                | <img src="Pictures/029-dipswitch.jpg" alt="Dipswitch" style="zoom: 33%;" />                |
| 1      | 2 wires cabled micro-switches        | <img src="Pictures/040-switchselect.jpg" alt="2 wires switch" style="zoom: 33%;" />   |
| 2      | 3 wires cabled micro-switches        | <img src="Pictures/040-switchprotect.jpg" alt="3 wires switch" style="zoom: 33%;" /> |

## Buttons<A id="a12"></A>

| Number | Buttons                 |                                                                              |
| ------ | ----------------------- | ---------------------------------------------------------------------------: |
| 1      | Reset button            | <img src="Pictures/040-resetbtn.jpg" alt="Reset" style="zoom: 33%;" />       |
| 1      | Power button with LED   | <img src="Pictures/040-pwrbtn.jpg" alt="ON/OFF button" style="zoom: 33%;" /> |

## Screws and mounts<A id="a13"></A>

| Number | Screws and sockets                                |                                                                                  |
| ------ | ------------------------------------------------- | -------------------------------------------------------------------------------: |
| 6      | M2x8 bolt (for back panel switches)               | <img src="Pictures/040-M2x8.jpg" alt="M2x8" style="zoom: 33%;" />                |
| 2      | M3x8 bolt (for HDMI connector)                    | <img src="Pictures/040-M3x8.jpg" alt="M3x8" style="zoom: 33%;" />                |
| 5      | 12mm mount with screw and bolts (front panel)     | <img src="Pictures/022A-support12.jpg" alt="12mm mount" style="zoom: 33%;" /> |
| 4      | 10mm mount with screw and bolts (LCD)             | <img src="Pictures/022B-support10.jpg" alt="10mm mount" style="zoom: 33%;" /> |
| 6      | 15mm mount with screw and bolts (bottom panel)    | <img src="Pictures/022C-support15.jpg" alt="15mm mount" style="zoom:33%;" />                      |

Note that 15mm mounts colour may vary.

## Cables<A id="a14"></A>

Bluety must be fit wristwrath cables cut at the right length for the reference case with a 190 mm depth and for the
following 3 configurations:

- SC126 alone
- SC126 with FP113 Tiny
- RC2014 Pro

To use a deeper case, Dupont cables can be used or custom cables with the right length.

| Number | Cables                                       |                                                                                    |
| ------ | -------------------------------------------- | ---------------------------------------------------------------------------------: |
| 1      | HDMI/microHDMI cable                         |           <img src="Pictures/040-hdmicable.jpg" style="zoom: 33%;" />              |
| 1      | Power button cable with 3 connectors         | <img src="Pictures/040-power.jpg" alt="Cable bouton ON/OFF" style="zoom: 33%;" />  |
| 1      | Red main power cable                         | <img src="Pictures/040-mainvcc.jpg" alt="Cable alimentation" style="zoom: 33%;" /> |
| 1      | Black main power cable                       | <img src="Pictures/040-maingnd.jpg" alt="Cable alimentation" style="zoom: 33%;" /> |
| 1      | 2 wires reset cable                          | <img src="Pictures/040-reset.jpg" alt="Cable reset" style="zoom: 33%;" />          |
| 1      | 4 wires I2C cable                            | <img src="Pictures/040-I2C.jpg" alt="Cable I2C" style="zoom: 33%;" />              |
| 2      | 12 wires Dupont cable                        | <img src="Pictures/040-dupont.jpg" alt="Cables connexion" style="zoom: 33%;" />    |

## Conclusion<A id="a15"></A>

Make sure you have all the components, buttons, cables before assembling.

Keep the integrated components safe in anti-static bags until the very last steps of assembly.
Wear an anti-static wrist strap linked to ground when handling them.
