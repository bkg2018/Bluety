# Preparation<A id="a16"></A>

In this part you will prepare the needed materials.

## Case<A id="a17"></A>

Bluety is designed for a blue metal case which can be found on most commercial sites with an electronics section:
AliExpress, Banggood, Amazon, eBay etc. In their search input field, enter the following: **"Blue Metal
Electronic Enclosure"** and choose the case with following dimensions:  **250 x 190 x 110 (mm)** in results.

<TABLE><TR><TD><img src="Pictures/attention.png" width="100px" /></TD><TD>There are other similar cases
with inferior dimensions which won't accept Bluety boards size, so make sure you choose the right dimensions.</TD></TR></TABLE>

| <img src="Pictures/00-AE.png" alt="AliExpress" style="zoom: 50%;" /> | <img src="Pictures/00-BG.png" alt="BangGood" style="zoom: 50%;" /> |
| ------------------------------------------------------------------- | ----------------------------------------------------------------- |
| <img src="Pictures/00-AZ.png" alt="Amazon" style="zoom: 50%;" />     | <img src="Pictures/00-EB.png" alt="eBay" style="zoom: 50%;" />     |

The cost is about 20 to 40 euros or dollars shipping included.

Beware the plastic frames holding the front and back panels may have their
corners slightly damaged during shipping. I had this problem with 3 cases I ordered.

<img src="Pictures/00-damagedcorner.jpg" style="zoom:50%;" />

If you plan to use another case you must ensure it has a width of at least 250mm, an height of at least 110mm and a depth
of at least 190mm. These dimensions are perfect for the SC126 and for all the RC2014 versions including the Pro with its
12 bus connectors. You will have to build some cables with adjusted lengths compared to this documentation.

The documentation contains templates to help drilling the fixation holes in the case bottom for SC126 and RC2014 Pro. These templates only applies to the reference dimensions.

## Tools inventory<A id="a18"></A>

The following table lists the tools needed to build Bluety.

| Tool                                                         |                                                              |
| ------------------------------------------------------------ | -----------------------------------------------------------: |
| Soldering station, preferably with heat control              | <img src="Pictures/001-station.jpg" alt="Soldering station" style="zoom: 50%;" /> |
| Solder wire (recommended: 0.6mm or 0.8mm)                    | <img src="Pictures/002-solderwire.jpg" alt="Solder wire" style="zoom: 50%;" /> |
| Fine pliers                                                  | <img src="Pictures/003-pliers.jpg" alt="Pliers" style="zoom: 50%;" /> |
| Angled pliers                                                | <img src="Pictures/004-pliers.jpg" alt="Pliers" style="zoom: 50%;" /> |
| Angled cutting pliers                                        | <img src="Pictures/005-pliers.jpg" alt="Cutting pliers" style="zoom: 50%;" /> |
| Solder sucker                                                | <img src="Pictures/006-pump.jpg" alt="Pump" style="zoom: 50%;" /> |
| Silicone mat (or isolating protection for working bench). It protects your bench from burning but also from scratching, and isolates circuits between them when they're lying on the bench surface. Clean the surface regularly during work to avoid metal and solder residues scratching the panels or printed boards or damage circuit conducting tracks. Pick up the metal and solder smithereens and clean the silicon mat under water then dry it. Do this regularly during assembly, do not let random residues accumulate on your working surface. |                          ![Mat](Pictures/006A-silicon.jpg) |
| Anti-static wristink the pincer to a metal ground. Do not link it to the earth pin on a plug of your home electric installation, you'd risk charging yourself with electricity instead of the contrary. If you don't have a special box you can link the pincer to the negative pin of a 5V trasformer or to the metallic case of a computer. | <img src="Pictures/007-bracelet.jpg" alt="Bracelet" style="zoom: 50%;" /> |
| Soldering iron cleaning kit| <img src="Pictures/008-tipscleaner.jpg" alt="Cleaning" style="zoom: 25%;" /><img src="Pictures/009-tipscleaner.jpg" alt="Nettoyage" style="zoom:25%;" /> |
| ALLEN keys for M2x8 and M3x8 screws                            | <img src="Pictures/010-allen.jpg" alt="Allen keys" style="zoom: 50%;" /> |
| Multi heads micro screwdriver | <img src="Pictures/011-screwdriver.jpg" alt="Screwdriver" style="zoom: 50%;" /> |
| Multimeter for voltage and resistors, possibly continuity test (diods) | <img src="Pictures/011A-multimeter.jpg" alt="Multimeter" style="zoom: 50%;" /> |
| Column drill for case bottom holes (ex: Dremel) with 3mm drill| |

## Advices<A id="a19"></A>

Respect the assembly orders to build a nice front panel woth well aligned components.

The connexion and assembling order is designed to ease the building process: do not jump over steps.

<TABLE><TR><TD><img src="Pictures/thisway.png" width="75px" /></TD><TD>Here's a method to limit damages in case of a soldering mistake:<BR>
<LI> solder <EM>only one leg</EM> of the component with as little solder as possible</LI>
<LI> check that the component is at the right place and has the right orientation if polarity is important</LI>
<LI> in case of mistake, you can easily unsolder the component and restart the step</LI>
<LI> when evrything looks correct only, solder the other legs as usual</LI>
<LI> complete the first leg soldering with more solder</TD></TR></TABLE>

If you solder all legs immediately, it will be difficult if not impossible to fix a mistake like a LED or a switch
soldered the wrong way. Having only one leg soldered also helps to tune components alignment before final soldering.

I recommend this method particularly for the front panel components which must be carefully aligned.
