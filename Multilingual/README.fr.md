
# <a name="h1"></a>Générateur de Markdown Multilingue MLMD

MLMD est un script PHP qui génère des fichiers Markdown dans plusieurs langues à partir de modèles multilingues, grâce à des directives placées dans les modèles pour distinguer les parties de chaque langue.

MLMD peut ajouter une table des matières et numéroter les titres dans les fichiers et les tables des matières.

L'utilisateur peut contrôler les langues, la génération des tables des matières et la numérotation des titres.

## <a name="toc"></a>Sommaire

-  [Générateur de Markdown Multilingue MLMD](README.fr.md#h1)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;1) [Installation](README.fr.md#h2)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.1) [Version PHP](README.fr.md#h3)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.2) [Emplacement de)) MLMD](README.fr.md#h4)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.3) [Utilisation d'un alias pour lancer MLMD](README.fr.md#h5)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;2) [Utilisation de MLMD](README.fr.md#h8)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.1) [MLMD launch syntax](README.fr.md#h9)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.2) [Templates file pathes and names](README.fr.md#h10)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.3) [Input files: `-i` argument](README.fr.md#h11)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.4) [Main file: `-main` argument](README.fr.md#h12)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.5) [Output mode html/md: `-out` argument](README.fr.md#h13)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.6) [Headings numbering: `-numbering` argument](README.fr.md#h14)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;3) [Writing templates files](README.fr.md#h17)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1) [End of lines](README.fr.md#h18)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.2) [Quoted text](README.fr.md#h19)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.3) [Variables](README.fr.md#h20)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.4) [Default text](README.fr.md#h21)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.5) [Directives](README.fr.md#h22)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.6) [Immediate vs enclosed effect](README.fr.md#h23)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.7) [Default directives and effects](README.fr.md#h24)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;4) [Declaring languages: `.languages` directive](README.fr.md#h25)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.1) [Syntax](README.fr.md#h26)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.2) [Notices](README.fr.md#h27)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3) [Example](README.fr.md#h28)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;5) [Generating for all languages: `.all((` directive](README.fr.md#h29)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5.1) [Syntax](README.fr.md#h30)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5.2) [Examples](README.fr.md#h31)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;6) [Default text: `.default((` or `.((` directive](README.fr.md#h32)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;6.1) [Syntax](README.fr.md#h33)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;6.2) [Examples](README.fr.md#h34)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;7) [Ignoring text: `.ignore` directive](README.fr.md#h35)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;7.1) [Syntax](README.fr.md#h36)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;7.2) [Example](README.fr.md#h37)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;8) [Generating for languages: `.<code>((`  directives](README.fr.md#h38)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8.1) [Syntax](README.fr.md#h39)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8.2) [Examples](README.fr.md#h40)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;9) [Generating Table Of Content: `.toc` directive](README.fr.md#h41)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;9.1) [Syntax](README.fr.md#h42)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;9.2) [Example](README.fr.md#h46)


out=
## <a name="h8"></a>Installation

MLMD est constitué d'un script principal `mlmd.php`et de dépendances  (`heading.class.php` et `generator.class.php`). Le script et ses dépendances peuvent être placés dans n'importe quel répertoire accessible à l'utilisateur.

### <a name="h4"></a>Version PHP

MLMD a été testé avec PHP 7.3 CLI.

Pour vérifier que PHP est accessible depuis une ligne de commande, tapez :

```code
php -v
```

Cela doit afficher des lignes de texte similaires à ce qui suit :

```code
PHP 7.3.20 (cli) (built: Jul  9 2020 23:50:54) ( NTS )
Copyright (c) 1997-2018 The PHP Group
Zend Engine v3.3.20, Copyright (c) 1998-2018 Zend Technologies
    with Zend OPcache v7.3.20, Copyright (c) 1999-2018, by Zend Technologies
```

Le répertoire d'installation de PHP et de ses fichiers de configuration peut être affiché avec la commande `php --ini`.

Les versions précédentes de PHP 7 peuvent fonctionner mais n'ont pas été testées. L'extension MultiByte (mb) est utilisée mais ne nécessite pas de réglage particulier car elle est intégrée par défaut dans les distributions standards de PHP 7.

### <a name="h5"></a>Emplacement de)) MLMD

Le script et ses dépendances doivent se situer dans un répertoire accessible à l'utilisatezur, par exemple :

* `~/phpscripts` sur macOS/Linux
* `%HOMEDRIVE%%HOMEPATH%\phpscripts` sur on Windows

Les paramètres du script sont décrits dans la partie [Utilisation de MLMD](#utilisation-de-mlmd)

