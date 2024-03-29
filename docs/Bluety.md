<A id="top">

[<img src="https://flagcdn.com/256x192/fr.png" width="25" height="19">](Bluety.fr.md)
[<img src="https://flagcdn.com/256x192/gb.png" width="25" height="19">](Bluety.md)

# Bluety - Case panels with I/O controls for Z-80 Retro Computers<A id="a1"></A>

Bluety is a set of printed circuit boards for a case hosting a retro-computer from the RC2014
family of Z-80 based retro-computers, particularly the SC126 kit from Stephen S. Cousins and the original RC2014 Pro
from Spencer Owens.

<img src="Pictures/finished.jpg" width="25%" alt="Finished Bluety" />

## Table of Contents<A id="toc"></A>

- [Bluety - Case panels with I/O controls for Z-80 Retro Computers](<#a1>)
- [Presentation](<01-Introduction.md#a2>)
  - [Content](<01-Introduction.md#a3>)
  - [Bus expansion](<01-Introduction.md#a4>)
  - [SC126 power switch](<01-Introduction.md#a5>)
- [List of Components](<02-Components.md#a6>)
  - [Case](<02-Components.md#a7>)
  - [Printed boards](<02-Components.md#a8>)
  - [Components](<02-Components.md#a9>)
  - [Connectors and sockets](<02-Components.md#a10>)
  - [Switches](<02-Components.md#a11>)
  - [Buttons](<02-Components.md#a12>)
  - [Screws and mounts](<02-Components.md#a13>)
  - [Cables](<02-Components.md#a14>)
  - [Conclusion](<02-Components.md#a15>)
- [Preparation](<03-Preparing.md#a16>)
  - [Case](<03-Preparing.md#a17>)
  - [Tools inventory](<03-Preparing.md#a18>)
  - [Advices](<03-Preparing.md#a19>)
- [Assembling Bus Daughter](<04-Assembling Bus Daughter.md#a20>)
- [Assembling Blink'n Switch](<05-Assembling Blink'n Switch.md#a21>)
  - [Back (part 1): sockets and components](<05-Assembling Blink'n Switch.md#a22>)
  - [Front: dip-switch sockets](<05-Assembling Blink'n Switch.md#a23>)
  - [Front: I/O connectors and power](<05-Assembling Blink'n Switch.md#a24>)
  - [Front: switches](<05-Assembling Blink'n Switch.md#a25>)
  - [Front: LEDs](<05-Assembling Blink'n Switch.md#a26>)
  - [Back: components (part 2)](<05-Assembling Blink'n Switch.md#a27>)
  - [Finishing](<05-Assembling Blink'n Switch.md#a28>)
- [Assembling the front panel](<06-Assembling Front Panel.md#a29>)
  - [LCD 4x20 display](<06-Assembling Front Panel.md#a30>)
  - [Power button](<06-Assembling Front Panel.md#a31>)
- [Assembling the back panel](<07-Assembling Back Panel.md#a32>)
  - [HDMI connector](<07-Assembling Back Panel.md#a33>)
  - [ROM Select and protect switches](<07-Assembling Back Panel.md#a34>)
- [Installing and wiring](<08-Installing and Wiring.md#a35>)
  - [Installing an SC126 / RC2014](<08-Installing and Wiring.md#a36>)
  - [Connecting BusDaughter and Blink'n Switch](<08-Installing and Wiring.md#a37>)
  - [Front panel](<08-Installing and Wiring.md#a38>)
  - [Back panel](<08-Installing and Wiring.md#a39>)
    - [HDMI (RC2014, SC126)](<08-Installing and Wiring.md#a40>)
    - [ROMs switches (SC126 only)](<08-Installing and Wiring.md#a41>)
    - [RESET](<08-Installing and Wiring.md#a42>)
    - [SC126](<08-Installing and Wiring.md#a43>)
    - [RC2014](<08-Installing and Wiring.md#a44>)
  - [Power button](<08-Installing and Wiring.md#a45>)
    - [SC126](<08-Installing and Wiring.md#a46>)
    - [RC2014](<08-Installing and Wiring.md#a47>)
- [Using Bluety](<09-Using.md#a48>)
  - [Control port (13/0D)](<09-Using.md#a49>)
  - [Input and Output ports A and B](<09-Using.md#a50>)
    - [Input from A or B port](<09-Using.md#a51>)
    - [Output to A or B port](<09-Using.md#a52>)
  - [LCD Screen: I2C Interface](<09-Using.md#a53>)
  - [LCD Display: parallel interfacing (SC126,RC2014 Pro)](<09-Using.md#a54>)

[^](#top)
[<img src="https://flagcdn.com/256x192/fr.png" width="25" height="19">](Bluety.fr.md)
[<img src="https://flagcdn.com/256x192/gb.png" width="25" height="19">](Bluety.md)
