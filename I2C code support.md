# Modifications SCM et BASIC pour I2C LCD

Il faut trois types d'ajouts :

* des modifs du code SCM et BASIC pour appeler ou tester les fonctions / options I2C
* du code de support I2C
* des variables et octets de contrôle en RAM

## Modifications code SCM


### ajout SCM

* instructions Z180 dans assembleur
* instructions Z180 dans désassembleur
* instrutcion "step over" dans le moniteur/debuggeur

### modif pour lcd / i2c

* call en RAM après init et char output : 3 octets RET NOP NOPs en ROM, surchargeables pour installer un JP vers le code de gestion LCD
* appel vers le jumper en ram dans le code SCM d'init et d'output char
* code de la mini console lcd
* code de support I2C
* code de support LCD I2C
 


## options de contrôle et variables (en ram)

- LCDControl: 1 octet de contrôle avec :
	* BIT_I2C: I2C (0 = off)
	* BIT_Parallel: parallel (0 = off)
	* BIT_Wrap: autowrap on/off
	* BIT_LCDOnly: n'afficher QUE sur LCD
	* BIT_Cursor: curseur on/off
	* BIT_Blink: curseur clignotant on/off 
	* BIT_Block: curseur bloc/underscore
- LCDPort: 1 octet pour le numéro de port (I2C SC126 : 0x0C, parallel = celui du sc129 etc) 
- I2CDevice: octet pour numéro de device I2C (0x27 pour controleur 8574T)
- LCDPOSX: 1 octet pour position X
- LCDPOSY: 1 octet pour position Y
- LCDCOLS: 1 octet pour le nombre de colonnes (20)
- LCDROWS: 1 octet pour le nombre ed lignes (4)
(- LCDStatus: 1 octet pour status actuel)

- LCD_RamCopy: 80 octets, copie de l'affichage


## Fonctions à ajouter au code I2C

- *LCDSetCur* : Positionner curseur
- *LCDChar* : Ecrire un caractère
- *LCDClear* : Clear screen
- *LCDScroll* : Scroll vers le haut et derniers ligne vide

### LCDSetCur : positionner le curseur

Position X dans B, Y dans C
Tous registres préservés

- si B >= LCDCOLS 
	B := LCDCOLS-1
- si C >= LCDROWS
	- C := LCDROWS-1
- LCDPOSX := B
- LCDPOSY := C 
- envoyer position curseur au LCD

### LCDClear : effacer l'écran et revenir en 0,0

Tous registres préservés

Vider la copie RAM (80 octets <= 0x20)
BC = 0
LCDSetCur

### LCDChar : écrire un caractère à la position actuelle

La position actuelle peut être 19, cela signifie qu'il reste une place avant le cr/lf
Si on est en mode wrap, on passe à la ligne suivante.
sinon, si in a un LF, on passe à la ligne et on enchaine sur l'écriture de caractère pour pos < 20,
sinon rien n'est inscrit ni modifié.

Faut-il gérer le caractère ctrl-H ?

- si posX >= LCDCOLS
	- TESTWRAP
	- si Z:
		- est-ce LF ?
			- si oui aller à :DOWRAP:
	- (autre caractère, on ignore, à voir pour gestion Ctrl-H)	
	- exit

DOWRAP:

- CRLF

&    /SENDCHAR: (on n'arrive ici que si posx est ok)

- envoyer le caractère
- le stocker dans la copie RAM
- posX += 1
- si posX >= LCDCOLS
	- TESTWRAP
		- si NZ
			- CRLF
- exit

### SCROLLUP

Pour remonter tout l'affichage vers le haut d'une ligne.
La position curseur ne change pas.

- décaler l'affichage avec une commande LCD ?
- Modifier la copie RAM
	- remonter les 60 derniers octets à la position 0
	- remplir les 20 derniers avec des espaces
	- recopie intégrale vers LCD (ligne par ligne)

### CR

Pour revenir en début de ligne.

- posX = 0
- SENDPOS

### CRLF

Pour passer à la ligne suivante, avec scroll automatique

- posX = 0
- posY += 1
- si posY >= 4
	- SCROLLUP
	- posY = 3
- SENDPOS

### SENDPOS

Pour envoyer la position curseur actuelle au LCD

- envoyer position au LCD

### TESTWRAP

Teste si le mode WRAP est ON
Positionne Z si 0, sinon on a NZ
A préservé, mais pas F (Z/NZ)

- test du bit dans l'octet de contrôle


