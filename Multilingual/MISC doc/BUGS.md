* pas de EOL à la fin de la ligne :

    .fr((français.)).en((english.))
    default text.fr((texte français..))

* ~~variable {main} non expansée si pas de fichier main => devrait être le premier fichier~~

* ~~{file} ne gère pas bien l'extension .base.md~~

TOC exemple level 1-3 actuel:
        # Chapter I) English File Title MLMD<A id="a1"></A>

        ## Default toc title<A id="toc"></A>

        - Chapter I) [English File Title MLMD](test.md#a1)
          - I-1) [Default title 2.1](test.md#a2)
              - I-1.1) [Title 3.1.1](test.md#a3)
          - I-2) [Title 2.2](test.md#a6)
        - II) [Secondary MLMD file](subdata/secondary.md#a7)
          - II-1) [Secondary title 1.1](subdata/secondary.md#a8)
          - II-2) [Secondary title 2.1](subdata/secondary.md#a9)
        - III) [Tertiary MLMD file](subdata/tertiary.md#a10)
          - III-1) [Tertiary title 1.1](subdata/tertiary.md#a11)
          - III-2) [Tertiary title 2.1](subdata/tertiary.md#a12)

Structure osuhaitée :

    # English File Title MLMD<A id="a1"></A>

    ## Default toc title<A id="toc"></A>

    - [English File Title MLMD](test.md#a1)
    - 1) [Default title 1](test.md#a2)
        - 1.1) [Title 1.1](test.md#a3)
    - 2) [Title 2](test.md#a6)
    - Chapter I) [Secondary MLMD file](subdata/secondary.md#a7)
      - I-1) [Secondary title 1.1](subdata/secondary.md#a8)
      - I-2) [Secondary title 2.1](subdata/secondary.md#a9)
    - Chapter II) [Tertiary MLMD file](subdata/tertiary.md#a10)
      - II-1) [Tertiary title 1.1](subdata/tertiary.md#a11)
      - II-2) [Tertiary title 2.1](subdata/tertiary.md#a12)
  - 
* Revoir le schéma de numérotation :
  * autoriser .topnumber 0 pour désactiver la numérotation niveau 1 dans un fichier
  * pas de préfixe ni numérotation niveau 1 sur fichier main ?
