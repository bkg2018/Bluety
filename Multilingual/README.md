# Multilingual Markdown Generator MLMD

MLMD is a PHP script which generate one or more Markdown files for each language from one or more multi-lingual markdown templates, using directives in the templates to distinguish each language parts.

Optionally, it adds a Table Of Content in each generated markdown file.

## Prerequisites

Make sure you have PHP 7.3 cli version accessible in PATH:

> php -v
> PHP 7.3.20 (cli) (built: Jul  9 2020 23:50:54) ( NTS )
> Copyright (c) 1997-2018 The PHP Group
> Zend Engine v3.3.20, Copyright (c) 1998-2018 Zend Technologies
>     with Zend OPcache v7.3.20, Copyright (c) 1999-2018, by Zend Technologies

Earlier versions of PHP 7 may work but have not been tested.

## Storing MLMD script

Put the PHP script in a directory you have easy access to, e.g.:

* `~/phpscripts` on macOS/Linux
* `%HOMEDRIVE%%HOMEPATH%\phpscripts` on Windows

The script is self-contained and doesn't require any other file or predefined directory.

### Using an alias to run MLMD

This is optional and allows you to type `mlmd` as if it were a command of your Operating System. If you don't use aliases, you'll have to type `php <your_path_to_mlmd>/mlmd.php` instead.

Adapt the commands below to the directory you stored the script into.

#### Linux / macOS / OS X

Put the following alias command in your shell startup (most likely `~/.bashrc`, `~/.zshrc` etc):

> alias mlmd=php ~/phpscripts/mlmd.php

#### Windows

Open NOTEPAD.EXE and put this command in the editor:

> doskey mlmd=php %HOMEDRIVE%%HOMEPATH%\phpscripts\mlmd.php $*

Save this as file `MLMD.CMD`or `mlmd.cmd` (case is ignored by Windows.) on your Desktop or any directory you like.

Create a shortcut to this CMD file (right-click then create shortcut).

Open the `shell:startup` directory (hit Windows key + R and type `shell:startup`).

Move the shortcut from its directory to this startup directory.

Restart Windows and login to your user account.

### Template files names

The files names for your Markdown templates must end with `.base.md` of `.mlmd`. Files with other extensions will be ignored.

MLMD can explore a directory tree and generate files for all the templates it finds. The generated files will be put in the same directory as their template.

See later in this README for directives syntax in the templates.

## How to Use MLMD

Edit your templates and name them with `.base.md` or `.mlmd` extension.

* Process a given file: use `-i` followed by a file path:

  > php ~/phpscripts/mlmd.php -i ~/project/README.base.md

* Process multiple files: use multiples `-i <filepath>`:

  > php ~/phpscripts/mlmd.php -i ~/project/README.base.md -i ~/project/HOWTOUSE.base.md

* Process a whole directory and subdirectories: change to this directory and give no parameters:

  > cd ~/project
  > php ~/phpscripts/mlmd.php

This last syntax will process any file found in the directory tree which ends by `.base.md` or `.mlmd`, including those found in sub directories. Other files will be ignored.

## Templates syntax

Your file templates must be named with the `.base.md` or `.mlmd` extension. They are normal text files so macOS or Windows text encoding is accepted but MLMD is also UTF-8 compliant.

Actions for generating the language specific files are set by *directives* in the base templates. 

Directives are commands beginning with a dot `.` followed by a keyword and parameters or, for most of them, by an opening marker `((` or an ending marker `))`.

Here's a summary of the available directives:

* `.languages` declares the languages used in the template(s) and drives the file(s) to generate.
* `.all((` starts a text section which will be put in all the language files.
* `.default((` or `.((` starts a section which will be put in the language files for which no language section is available on the same line.
* `.ignore((` starts a text section which will not be put in any generated file.
* `.<code>((` starts a text section which will be put only in the generated file for language `<code>` which has been declared in the `.languages` directive.
* `.))` ends a section started by one of the `.((` directives and returns to the previous directive effect.
* `.toc` generates a Table Of Contents using choosen file headings levels.

Directives are not case sensitive: `.fr((` is the same as `.FR((`.

Each opening directive with a `((` marker takes effect until a matching `.))` is met or another directive takes control.

> Notice that headings (starting with `*`) must be alone on a line, as well as the `.LANGUAGES` directive.

Details for each directive follow.

## Declaring languages: `.LANGUAGES`

The `.languages` directive declares the possible languages which occur in the templates and optionally tells which one is the `main` language.

### Syntax

> .languages <code>[[,<code>]...] [main=<code>]

Each  `<code>` declares a language which can then be used with `.<code>((` directives to start text sections for the `<code>` language.

The optional `main=<code>` parameter tells which language is the main language: files generated for thhis main language will have an `.md` exxtension instead of a `.<code>.md` extension. As an example, `README.base.md` will generate a `README.md` for the main language, and a `README.<code>.md` for other language codes. This is particularly useful with Git deposits which require a `README.md` file at the deposit root.

### Notices

* No file is generated before the `.languages` directive is met: any preceeding text will be ignored.
* The `.languages` directive is global so you need only to put it in the first processed file. If you have doubts about which file will be processed first, you can put the directive in all of your templates.
* After the `.languages` directive, the generator will send output to all languages until a directive changes this. See the `.ALL((` directive documentation. 

### Example

> .LANGUAGES en,fr main=en

Generated files will be named with a `.md` extension for the `en` language and with `.fr.md` for the `fr` language.

## Generating for all languages: `.ALL((`

The `.all((` directive starts a section of text which will be put in each of the languages files declared in the `.languages` directive.

This directive is ended or suspended by:

* The `.))` directive which returns to previous state.
* The `.<code>((` directives which start a language specific portion of text.
* The `.ignore((` directive which starts ignored text.
* The `.default((` or `.((` directive which starts the default value for a portion of text in a line.

By default, any text outside directives appearing affter the `.languages`directive is generated in all the languages files as if it were in an `.all((` section so this directive can be considered optional.

### Syntax

> .all((

### Examples

> .all(( 
> text for all languages
> .))

It can also be put inline within text or headings:

> *text to generate for a language* .all((*text for all languages*.)) *other text for a language*

## Default text: `.DEFAULT((` or `.((`

The `.default((`  or `.((` directive starts a default text section which will be put in the generated language files for which no specific language section is available on the same line.

This directive only has effect on the line where it occurs. Only the first `.default((` or `.((` directive on the line will have an effect, others appearing later on the line will be ignored.

Notice that text in `.default((` is **not** the same as `.all((`. Text for all languages will go in every generated files, while default text will only go in files for which there is no language section on the same line.

The goal of the `.default((` directive is to prepare the original text and headings in a common language like english, then add language specific sections while still having the default text for languages which are not translated yet.

See examples below.

### Syntax

> .default((

or: 

> .((

### Examples

The most obvious use of default text is in headings, as they are necessarily contained in one line. But it also works on text blocks, on a line basis.

> # .default((Main Title.)) .fr((Titre principal.))

This will put `# Main Title` in all the generated files except the `.fr.md` file where the generator will put `# Titre Principal`.

For text blocks, the default directive is a little sensitive because it will only apply to languages which have absolutely no specific section on the same line. The common use is to avoid any text outside a directive or at least, to keep translated sections near the matching default section, like in the example below:

> .((This is the default original text..)) .fr(ceci est la traduction en français..))

This will put `This is the default original text.` in all files except the `.fr.md` file where it will put `Ceci est la traduction en français.`.

Text outside directives always go in all generated files, whether there is a default section or not.

If you mix default text with text outside directives or use more than one default directive on a line, you will have side effects and probably not what you expect. Only the first default section will be used by the generator, and default text will have no effect if there is an `.all((` section.

## Ignoring text: `.IGNORE`

The `.ignore` directive starts an ignored section of text. Ignored text won't be put in any generated file. It is useful for TODO, comments, work-in-progress documentation parts which are not ready or appropriate for final generated files.

This directive is ended by:

* The `.))` directive which returns to previous state.
* The `.all((` directive which starts a section for all languages.
* The `.<code>((` directives which start a language specific portion of text.
* The `.default((` or `.((` directive which starts the default value for a portion of text in a line.

#### Syntax

> .ignore(( 

#### Example

> .ignore(( 
> text to ignore
> .))

It can also be put inline within text or titles:

> *text to generate* .ignore((*text to ignore*.)) *other text to generate*
> # Title .ignore((ignore this.))

### Generating for one language: the `.<code>((` directive

The `.<code>((` directive starts a section of text which will be put in the generated file for the `<code>` language. The language `<code>` must have been declared in the `.languages` directive or the section is ignored.

This directive is ended or suspended by:

* The `.))` directive which returns to previous state.
* The `.<code>((` directives which start language specific sections.
* The `.all((` directive which starts a section for all languages.
* The `.ignore` directive which starts ignored text.


#### Syntax

> .<code>((

#### Example

> .en(( 
> text for English language only
> .))

It can also be put inline within text or titles:

> # .fr((Titre en Français.)) .en((English Title.))

Notice that the apparently ending '.' in titles is in fact the dot from the `.))` directive. You can
avoid this misleading visual using spaces:

> # .fr(( Titre en Français .)) .en(( English Title .))

Starting and ending spaces are trimmed from text sections.

### Generating table Of Content: the .TOC directive

The `.toc ` directive generates a Table Of Contents for the current file using choosen header levels.

The headers levels are defined by the `#` prefixes in Markdown syntax: `#` is header level 1, `##` is level 2 etc.

By default, level 1 is ignored - considered as the file title - and levels 2 to 4 are put in TOC.

The Table of Contents has one link for each accepted heading. It will be put at the place of the `.toc` directive.

#### Syntax

The `.toc` directive must be alone on one line.

> .toc [m][-][n] [title]

* If no level `m` and `n` is given, the TOC will contain heading levels 2 to 4 (i.e: `##` to `####`).
* If `m` is given alone, TOC titles will be level `m` headings only.
* If `m-` is given without `n`, TOC titles will be level `m` to level 9 headings.
* If `m-n` is given, TOC titles will be level `m` to level `n` headings.
* If `-n` is given, TOC titles will be level 1 to level `n` headings.
* If no title is given, `Table Of Contents` will be used.

#### Example

> .toc 2-4 .fr((Table des Matières.)) .all((Table of Contents.))

This directive does the same as `.toc` alone and generates a TOC using the headings `##` to `####` found in the file.

The table title will be `Table des Matières` in the `.fr.md` file, and `table Of Contents` in all the other languages.
