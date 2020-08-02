
# <a name="h1"></a>Multilingual Markdown Generator MLMD

MLMD is a PHP script which generate one or more Markdown files for a set of declared languages from one or more multilingual markdown templates, using directives in the templates to distinguish each language parts.

MLMD can add a Table Of Content in the generated Markdown files and number headings in all files and in tables of Content.

The user has full control over the generated languages, the table of content generation and the headings numbering.

## <a name="toc"></a>Table Of Contents

-  [Multilingual Markdown Generator MLMD](README.md#h1)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;1) [Installation](README.md#h2)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.1) [PHP version](README.md#h3)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.2) [Storing](README.md#h4)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1.3) [Using an alias to launch MLMD](README.md#h5)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;2) [How to Use MLMD](README.md#h8)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.1) [MLMD launch syntax](README.md#h9)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.2) [Templates file pathes and names](README.md#h10)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.3) [Input files: `-i` argument](README.md#h11)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.4) [Main file: `-main` argument](README.md#h12)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.5) [Output mode html/md: `-out` argument](README.md#h13)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2.6) [Headings numbering: `-numbering` argument](README.md#h14)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;3) [Writing templates files](README.md#h17)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1) [End of lines](README.md#h18)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.2) [Quoted text](README.md#h19)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.3) [Variables](README.md#h20)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.4) [Default text](README.md#h21)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.5) [Directives](README.md#h22)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.6) [Immediate vs enclosed effect](README.md#h23)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.7) [Default directives and effects](README.md#h24)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;4) [Declaring languages: `.languages` directive](README.md#h25)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.1) [Syntax](README.md#h26)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.2) [Notices](README.md#h27)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3) [Example](README.md#h28)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;5) [Generating for all languages: `.all((` directive](README.md#h29)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5.1) [Syntax](README.md#h30)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5.2) [Examples](README.md#h31)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;6) [Default text: `.default((` or `.((` directive](README.md#h32)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;6.1) [Syntax](README.md#h33)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;6.2) [Examples](README.md#h34)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;7) [Ignoring text: `.ignore` directive](README.md#h35)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;7.1) [Syntax](README.md#h36)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;7.2) [Example](README.md#h37)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;8) [Generating for languages: `.<code>((`  directives](README.md#h38)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8.1) [Syntax](README.md#h39)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8.2) [Examples](README.md#h40)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;9) [Generating Table Of Content: `.toc` directive](README.md#h41)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;9.1) [Syntax](README.md#h42)
<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;9.2) [Example](README.md#h46)


out=
## <a name="h8"></a>Installation

MLMD consists of a main script `mlmd.php` and a few dependencies files. (`heading.class.php` and `generator.class.php`.) The script and its dependancies files can be put anywhere at user choice.

### <a name="h4"></a>PHP version

MLMD has been tested with PHP 7.3 cli version.

To make sure PHP is accessible from a command line:

```code
php -v
```

should display:

```code
PHP 7.3.20 (cli) (built: Jul  9 2020 23:50:54) ( NTS )
Copyright (c) 1997-2018 The PHP Group
Zend Engine v3.3.20, Copyright (c) 1998-2018 Zend Technologies
    with Zend OPcache v7.3.20, Copyright (c) 1999-2018, by Zend Technologies
```

The directory where the PHP installation and its setting files lie can be displayed with `php --ini`.

Earlier versions of PHP 7 may work but have not been tested. The Multibyte extension (mb) is needed but should not imply a specific setting as it should be embedded in standard PHP 7.3 distributions.

### <a name="h5"></a>Storing

The PHP script and its dependencies must be put in a directory with easy user access, e.g.:

* `~/phpscripts`  macOS/Linux
* `%HOMEDRIVE%%HOMEPATH%\phpscripts`  on Windows

Parameters that can be passed to the script are described in [How To Use MLMD](#how-to-use-mlmd)

