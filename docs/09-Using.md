# IX) Using Bluety<A id="a47"></A>

The input and output ports on a Z-80 are controlled by the IN and OUT instructions, as well as some derivatives on the Z180 of the SC126.
BASIC also has IN and OUT instructions to read or write a byte on a port. The LCD screen is controlled on the SC126 by the I2C bus,
using a library you put in your programs, or using Bluety specific SCM or BASIC versions which are in development.
These versions optionally send an echo of their output to the LCD screen. The echo and LCD console poarameters are
determined by RAM variables ag specific addresses.

## IX-1) Control port (13/0Dh)<A id="a48"></A>

The control port is  fixed on the 13 decimal / 0D hexadecimal: this is used by design in both control softwares for the SC126.

The 8 Bluety LEDs below the LCD screen are direct echoes of the last byte written to the control port.

- The monitor *Small Computer Monitor* dispklays the result of its startup tests on this port.
- The RomWBW booting process displays its progression on this port.
- Any program can switch these LEDs on or off by sending a 8-bits data on the control port. The leftmost LED represents
the most significant bit: writing 80h will light this LED alone.

<TABLE><TR><TD><img src="Pictures/thisway.png" alt="Advice" width="75px" /></TD><TD>
Remark: the control port retains the last written byte, so to switch LEDs off you mlust explicitely write a 0. This is
particularly true with RomWBW which lights all 8 LEDs during its boot process and leaves them in this state.
</TD></TR></TABLE>

## IX-2) Input and Output ports A and B<A id="a49"></A>

These 2 ports have 8 LEDs for the input port and 8 LEDs for the output port. In both cases, the most significant bit
(bit 7) corresponds to the leftmost LED.

The port number for both of these ports can be choosen with the 8 triggers of their respective dip-switch, on which the leftmost
trigger also represents the most significant bit in the port number: to select the 80h port, put the leftmost trigger UP and all the
others DOWN.

### IX-21) using the A or B input port<A id="a50"></A>

Both input ports have an 8-bits female header for +5V input, 8 control LEDs, and 8 input switches.

| Step  | Description                                                  |                                                        |
| ----- | :----------------------------------------------------------- | -----------------------------------------------------: |
| 1     | Choose the port number and put the corresponding switches on the port select dip-switch up for bits 1, down for bits 0. The leftmost most significant bit is on the leftmost switch. For example, to select the port number 7 you put the 3 right most switches up and all the pthers down. | <img src="Pictures/97-portselect.jpg" width="400px" /> |
| 2     | Put the input source 3-positions switch in the middle position and connect your input wires to the input header. |     <img src="Pictures/096-input.jpg" width="300px" /> |
| 3     | Each switch can be set up to force a bit at 1 or down to force it at 0. |          <img src="Pictures/TODO.PNG" width="300px" /> |
| 4     | The control switch at the LEDs top right enable or disables all LEDs lighting. This saves a few current if case you need it. Each LED reflects the state of its corresponding bit in the input header or LED switch. In bottom position, the switch disables LEDs whatever the state of the input bit, which avoid consuming input current just for the LEDs. |          <img src="Pictures/TODO.PNG" width="300px" /> |
| 5     | To read the port input value, use:<br />● the Z-80 `IN` instruction family<br />● the Z-180 `IN0` instruction<br />● the BASIC `INP()` function<br />● the SCM `IN` command<br />The received data will have bits 1 on the input lines which receive +5V and the corresponding LEDs will be lighted (if the global control switch is not down). The bits for input lines at GND level or significantly less than +5V will be at 0 and the LED will be off. |                                                        |

Beware that input lines directly feed integrated conponents and LEDs, so take care of not drawing more than a few milliampers and not much more
than +5V or you could burn components and make your input port definitely useless.

Generally, less than +4.7V on an input line will be considered as a 0 bit.

### IX-22) Using the A or B output port<A id="a51"></A>

| Step  | Description                                                  |                                                        |
| ----- | ------------------------------------------------------------ | -----------------------------------------------------: |
| 1     | Choose the port number and put the corresponding switches on the port select dip-switch up for bits 1, down for bits 0. The leftmost most significant bit is on the leftmost switch. For example, to select the port number 7 you put the 3 right most switches up and all the pthers down. | <img src="Pictures/97-portselect.jpg" width="400px" /> |
| 2     | Connect up to 8 output wires to the output header.          |            <img src="Pictures/TODO.PNG" width="300" /> |
| 3     | The switch at the LEDs bottom right enables or disables the LEDs lighting. When they're disabled, the output header receives all the output current, when they're enabled they consume a few milliampers of the output load. |            <img src="Pictures/TODO.PNG" width="300" /> |
| 4     | To write a data on the port, use:<br />● the Z-80 `OUT` instruction family<br />● the `OUT0` Z-180 instruction<br />● the BASIC `OUT` instruction<br />● the SCM `OUT` command<br />+5V will be sent on the lines with a bit at 1 and the matching LED will be lighted. |            <img src="Pictures/TODO.PNG" width="300" /> |
| 5     | You can use the additional +5V/GND header to feed external devices like circuits with sensors or relays, independently from any switch or LED. |                                                        |

<TABLE>
<TR><TD><img src="Pictures/thisway.png" alt="Conseil" width="75px" /></TD><TD>
Remark: Output ports retain their last data, so to switch all lines and LEDs to GND level you must explicitely write a 0 to the output port.
</TD></TR>
<TR><TD><img src="Pictures/thisway.png" alt="Conseil" width="75px" /></TD><TD>
You can use the dipswitch port select to spy any of the Z-80 port, for example gthe Z-180 internal ports on the SC126 
or the ports used by a ROM with BusRaider.
</TD></TR></TABLE>

## IX-3) LCD Screen: I2C Interface<A id="a52"></A>

On the SC126, the LCD display is controlled through the I2C bus on port 0Ch. The kit display is equipped with an I2C adapter and
doesn't need any additionnal board and the only thing to do is connect the 4-wires cable between the SC126 I2C header and the LCD
adapter.

The LCD adapter doesn't feature an output I2C port to insert it in a chain and it must be last, so if you want to plug another I2C device
you will have to put it before the LCD display and make sure the device I2C IDs are not conflicting. Refer to I2C and your devices
specifications.

Instead of using the kit I2C display you may buy a similar display without I2C and connect it using a parallel port RC2014 board or
an I/O controller RC2014 board. Stephen S. Cousins site shows a number of ways to do this and offers Z-80 code to control an LCD
display using OUT instructions. The Bluety kit doesn't include options for this method by itself and you will need an I/O or PIO board
as described on Stephen S. Cousins site.

## IX-4) LCD Display: parallel interfacing (SC126,RC2014 Pro)<A id="a53"></A>

The RC2014 Pro kit has no I2C bus and cannot control Bluety LCD screen. In this configuration, one way to have an LCD display
is to use a parallel interfacing as described in [Stephen S. Cousins example](https://smallcomputercentral.wordpress.com/example-alphanumeric-lcd/),
using either an 8-bit output port controller board, either a Z-80 PIO circuit, both adressing the LCD screen 8 control pins.

With SC126 you can also use this solution if you want to keep the I2C bus for other uses.

<TABLE><TR><TD><img src="Pictures/attention.png" width="100px" /></TD><TD>
When ordering your Bluety kit, make sure you select the right LCD screen type <EM>without I2C adapter</EM>, 
or you will hzve to unsolder the adapter, which is very difficult
</TD></TR></TABLE>

You will have to use a board for the output port control. If you don't use a Z-80 PIO board, you have at least three
options:

- The 8-bits [SC129 input/output board](https://smallcomputercentral.wordpress.com/sc129-digital-i-o-rc2014/) from Stephen S. Cousins
  (buy on [Tindie](https://www.tindie.com/products/tindiescx/sc129-digital-io-module-kit-for-rc2014/)): it features an output
  connector on which to connect an 8-wires cable for the LCD screen control.
- The official RC2014 [Digital Output Module](http://rc2014.co.uk/modules/digital-io/) from Spencer Owens
  (buy on [Tindie](https://www.tindie.com/products/Semachthemonkey/digital-output-module-for-rc2014-z80-computer/)):
  you will have to configure the port number base as explained in Spencer's documentation and use the pins of one of the
  three available ports to connect an 8-wires cable.
- The [SC103 Z-80 PIO board](https://smallcomputercentral.wordpress.com/sc103-z80-pio-module-rc2014/) from Stephen S. Cousins:
  you will have to configure the port number base as explained in Stephen's documentation and use the pins of one of the
  three available ports to connect an 8-wires cable.

Le contrôle matériel sera le même quelle que soit la carte. Le reste concerne l'installation logicielle qui contrôle le SC216 ou le RC2014.

A ce jour, je n'ai pas encore développé de logiciel pour cette interface matérielle.

## IX-5) Ecran LCD : Interface logicielle<A id="a54"></A>

Actuellement, l'interface LCD logicielle est développée pour le bus I2C sur un SC126. Deux logiciels sont en cours de développement :

* Une version de Small Computer Monitor 1.2 (destiné au SC126)
* Une version du BASIC NASCOM adapté par Grant Searles

Lorsque ces logiciels seront achevés, une version utilisant un port de sortie en sera dérivée.

Une version CP/M du BASIC-80 est à l'étude.

Fonctions :

- sortie en mode console avec gestion backspace et linefeed et scroll automatique
- scroll
- effacement
- positionnement curseur
- retour chariot auto ou ignoré
- affichage texte 
- programmation 16 caractères user
- codes de contrôle ou caractères user pour les codes 0 à 15
- curseur bloc ou underline
- curseur visible ou non
- curseur clignotant ou fixe
- écho device normal ou non (SCM, BASIC)
