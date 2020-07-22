# Multilingual Markdown Generator MLMD

MLMD is a PHP script which generate one or more Markdown files for each language from one or more multi-lingual markdown templates, using directives in the templates to distinguish each language parts.

Optionally, it adds a Table Of Content in each generated markdown file.

## Prerequisites

Make sure you have PHP 7.3 cli version accessible in PATH:

```code
php -v
PHP 7.3.20 (cli) (built: Jul  9 2020 23:50:54) ( NTS )
Copyright (c) 1997-2018 The PHP Group
Zend Engine v3.3.20, Copyright (c) 1998-2018 Zend Technologies
    with Zend OPcache v7.3.20, Copyright (c) 1999-2018, by Zend Technologies
```

Earlier versions of PHP 7 may work but have not been tested.

## Storing MLMD script

Put the PHP script in a directory you have easy access to, e.g.:

* `~/phpscripts` on macOS/Linux
* `%HOMEDRIVE%%HOMEPATH%\phpscripts` on Windows

The script is self-contained and doesn't require any other file or predefined directory.

Parameters that can be passed to the script are discribed in [How To Use MLMD](#how-to-use-mlmd)

### Using an alias to run MLMD

This is optional and allows you to type `mlmd` as if it were a command of your Operating System. If you don't use aliases, you'll have to type `php <your_path_to_mlmd>/mlmd.php` instead.

Adapt the commands below to the directory where you stored the script.

#### Linux / macOS / OS X

* Put the following alias command in your shell startup (most likely `~/.bashrc`, `~/.zshrc` etc):

  ```code
  alias mlmd=php ~/phpscripts/mlmd.php
  ```

#### Windows

* Open NOTEPAD.EXE and put this command in the editor:

  ```code
  doskey mlmd=php %HOMEDRIVE%%HOMEPATH%\phpscripts\mlmd.php $*
  ```

* Save this as file `MLMD.CMD`or `mlmd.cmd` (case is ignored by Windows.) on your Desktop or any directory you like.
* Create a shortcut to this CMD file (right-click then create shortcut).
* Open the `shell:startup` directory (hit Windows key + R and type `shell:startup`).
* Move the shortcut from its directory to this startup directory.
* Restart Windows and login to your user account.

### Template files names

The files names for your Markdown templates must end with `.base.md` of `.mlmd`. Files with other extensions will be ignored.

When no specific files parameters are given to the script, MLMD will explore the directory tree where it starts and generate files for all the templates it finds. The generated files will be put in the same directory as their template.

See [Templates and Directives](#templates-and-directives) for directives syntax in the templates.

## How to Use MLMD

Edit your templates and name them with `.base.md` or `.mlmd` extension.

* Process a given file: use `-i <filepath>`:

  ```code
  php ~/phpscripts/mlmd.php -i ~/project/README.base.md
  ```

* Process multiple files: use multiples `-i <filepath>`:

  ```code
  php ~/phpscripts/mlmd.php -i ~/project/README.base.md -i ~/project/HOWTOUSE.base.md
  ```

* Process a whole directory and subdirectories: change to this directory and give no `-i` parameters:

  ```code
  cd ~/project
  php ~/phpscripts/mlmd.php
  ```

  This last syntax will process any file found in the directory tree which ends by `.base.md` or `.mlmd`, including those found in sub directories. Other files will be ignored.

## Templates and Directives

Your file templates must be named with a `.base.md` or `.mlmd` extension. They are normal text files so macOS or Windows text encoding is accepted but MLMD is UTF-8 compliant so macOS and Windows encoding could have side effect, as any character above code 127 will be invalid UTF-8.

Actions for generating the language specific files are set by *directives* in the base templates. 

Directives are commands beginning with a dot `.` followed by a keyword and parameters or, for most of them, by an opening marker `((` or an ending marker `))`.

Here's a summary of the available directives:

* `.languages` declares the languages used in the template(s) and drives the file(s) to generate.
* `.all((` starts a text section which will be put in all the language files.
* `.default((` or `.((` starts a section which will be put in the language files for which no language section is available on the same line.
* `.ignore((` starts a text section which will not be put in any generated file.
* `.<code>((` starts a text section which will be put only in the generated file for language `<code>` which has been declared in the `.languages` directive.
* `.))` ends a section started by one of the `.((` directives and returns to the previous directive effect.
* `.toc` generates a Table Of Contents using headings.

Directives are not case sensitive: `.fr((` is the same as `.FR((`.

### Directives immediate and embedded effect

The `.languages` and `.toc` directives have an *immediate effect*. Although they can be placed anywhere, their goal imply that they better stay alone on a single isolated line, and preferably at the beginning of template files.

The other directives start with an opening `.<directive>((` marker which *embeds their effect* until a matching `.))` is met, or until another directive takes control.

> These directives can be embedded: each `.<directive>((` opening will suspend the previous directive effect, and the matching `.))` closing will resume it.

### Defaults directives and effects

Details will follow but it must be mentionned that the script has some defaults and that directives themselves also have defaults settings.

* Anything preceeding the `.languages` directive is *ignored*. See [Declaring languages](#declaring-languages-languages).
* After the `.languages` directive, the generator acts as if a `.all((` directive had been met, so any text will go into all the languages files. See [Declaring languages](#declaring-languages-languages).
* The `.default((` or `.((` directive will only have effect on languages which have not a defined content yet. See [Default directive](#default-text-default-or-).
* The `.toc` directive has default values which generate an table of contents for local headings in the current file only. See [TOC](#generating-table-of-content-toc).

## Declaring languages: `.LANGUAGES`

The `.languages` directive declares the possible languages which can be found in the templates and optionally tells which one is the *main* language.

The *main* language has files generated without the language code suffix, e.g. *README.md* while other languages will have the language code suffix, e.g. *README.fr.md*.

### Syntax

```code
.languages <code>[[,<code>]...] [main=<code>]
```

Each  `<code>` declares a language which can then be used with `.<code>((` directives to start text sections for the `<code>` language.

The optional `main=<code>` parameter tells which language is the main language: files generated for thhis main language will have an `.md` exxtension instead of a `.<code>.md` extension. As an example, `README.base.md` will generate a `README.md` for the main language, and a `README.<code>.md` for other language codes. This is particularly useful with Git deposits which require a `README.md` file at the deposit root.

### Notices

* No file is generated before the `.languages` directive is met: any preceeding text will be ignored.
* The `.languages` directive is global so you need only to put it in the first processed file. If you have doubts about which file will be processed first, you can put the directive in all of your templates.
* After the `.languages` directive, the generator will send output to all languages until a directive changes this. See the `.ALL((` directive documentation. 

### Example

```code
.languages en,fr main=en
```

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

```code
.all((
```

### Examples

Directives can always be alone on a line, surrounding the text they act on:

```code
.all((
text for all languages
.))
```

They can also be put inline within text:

```code
.en((text for 'en' language .all((text for all languages.)) rest of text for 'en' language.))
```

And they can also be embedded within headings:

```code
# .en((Heading text for English .all(added text for all languages.)) heading text for English again .)) text for all languages
```

## Default text: `.DEFAULT((` or `.((`

The `.default((`  or `.((` directive starts a default text section which will be put in the generated language files for which no specific language section is yet available on the same line.

This directive only has effect on the line where it occurs, and only if no content has been stored yet. Only the first `.default((` or `.((` directive on the line will have an effect, others appearing later on the line will be ignored.

Notice that text in `.default((` is **not** the same as `.all((`. Text for all languages will go in every generated files, while default text will only go in files for which there is no language section on the same line.

The goal of the `.default((` directive is to prepare the original text and headings in a common language like english, then add language specific sections on the fly while still having the default text for languages which are not translated yet.

See examples below.

### Syntax

```code
.default((
```

or:

```code
.((
```

### Examples

The most obvious use of default text is in headings, as they are necessarily contained in one line. But it also works on text blocks, on a line basis.

```code
# .default((Main Title.)) .fr((Titre principal.))
```

This will put `# Main Title` in all the generated files except the `.fr.md` file where the generator will put `# Titre Principal`.

For text blocks, the default directive is a little sensitive because it will only apply to languages which have absolutely no specific section on the same line. The common use is to avoid any text outside a directive or at least, to keep translated sections near the matching default section, like in the example below:

```code
.((This is the default original text..)) .fr(ceci est la traduction en français..))
```

This will put `This is the default original text.` in all files except the `.fr.md` file where it will put `Ceci est la traduction en français.`.

Notice that text *outside directives* will go in all generated files, whether there is a default section or not.

If you mix default text with text outside directives or use more than one default directive on a line, you will have side effects and probably unexpected results. Only the first default section will be used by the generator, and any text in an `.all((` section will cancel the default text.

To get predictable and expected results, make sure you put your default section first on the line and you don't put text outside language sections. (See [Generating for languages](#generating-for-languages-code).)

## Ignoring text: `.IGNORE`

The `.ignore` directive starts an ignored section of text. Ignored text won't be put in any generated file. It is useful for TODO, comments, work-in-progress documentation parts which are not ready or appropriate for final generated files.

This directive is ended or suspended by:

* The `.))` directive which returns to previous state.
* The `.all((` directive which starts a section for all languages.
* The `.<code>((` directives which start a language specific portion of text.
* The `.default((` or `.((` directive which starts the default value for a portion of text in a line.

### Syntax

```code
.ignore((
```

### Example

```code
.ignore((
text to ignore
.))
```

It can also be put inline within text or headings:

```code
text to generate .ignore((text to ignore.)) other text to generate
# Title for all languages .ignore((ignore this.))
```

## Generating for languages: `.<code>((`

The `.<code>((` directive starts a section of text which will be only put in the generated file for the `<code>` language and no other file. The language `<code>` must have been declared in the `.languages` directive or the section is ignored.

This directive is ended or suspended by:

* The `.))` directive which returns to previous state.
* Another `.<code>((` directive which start another language specific section.
* The `.all((` directive which starts a section for all languages.
* The `.ignore` directive which starts ignored text.

Language sections must be closed by a matching `.))`. Although sections can be chained, it is recommended to close a section before beginning an other one, else you'll have to close all of them at the end of sections. See examples below for language chaining.

### Syntax

```code
.<code>((
```

### Examples

```code
.en((text for English language only.))
```

It can also be put inline within text or titles:

```code
# .fr((Titre en Français.)) .en((English Title.))
```

Notice that the apparently ending '.' in titles is in fact the dot from the `.))` directive. You can
avoid this somewhat misleading visual effect by using spaces:

```code
.fr(( Texte en Français .)) .en(( English text .))
```

Ending spaces are trimmed from the generated text.

As mentioned above, you can chain language sections without closing them, but you'll syill have to close each one in the end. The line below has the same effect as the previous example:

```code
.fr(( Texte en Français .en(( English text .)).))
```

Beware that if you don't close a section, it stays active until it is closed. In the next example, the closing on the first line ends the `.en` section, but the `.fr` stays active and thhe following text will be generated in the `.fr.md` file, until another `.))` is found.

```code
.fr(( Texte en Français .en(( English text .))
Ce texte est dans la section en français. .))
Now this text is in the `all` section.
# .fr(( Titre en Français .en(( English Title .))
```

## Generating Table Of Content: `.toc`

The `.toc` directive generates a Table Of Contents for the current file, using choosen header levels.

The header levels are defined by the `#` prefixes in Markdown syntax: `#` is header level 1, `##` is level 2 etc.

By default, level 1 is ignored: it is considered as the file title, and levels 2 to 4 are put in TOC. But level 1 can be useful if you want to build a global TOC for a set of files. 

The Table of Contents has one link for each accepted heading. It will be put at the place of the `.toc` directive.

### Syntax

The `.toc` directive must be alone on its line. Most of the time, you'll put the TOC after the file title and some introduction. A default TOC with no parameters will build a table of contents for the current file with ## to #### headings.

```code
.TOC [level=[m][-][n]] [title=m,"<title text>"] [number=m:<symbol><sep>[,...]]
```

#### `level` parameter

This parameter sets the headings which will appear in the TOC. 

Syntax for this parameter is `level=[m][-][n]`:

* If neither `m` nor `n` are given, the TOC will contain heading levels 2 to 4 (matching headings `##` to `####`).
* If `m` only is given, TOC titles will be level `m` headings only.
* If `m-` is given without `n`, TOC titles will be level `m` to level 9 headings.
* If `m-n` is given, TOC titles will be level `m` to level `n` headings.
* If `-n` is given, TOC titles will be level 1 to level `n` headings.

#### `title` parameter

Syntax for this parameter is `title=m,"<title text>"`:

* `t` is the heading level for the TOC title itself. Level 2 is recommended. (The TOC title will be a ## heading.)
* The title text can use language, all, ignore and default directives.
* If no title text is given, `Table Of Contents` will be used.
* If no title parameter is given, level 2 will be used.
* The double quotes `"` around the title text are mandatory. If they are missing, an error is displayed and the rest of text following `title=` on the line is used as title text.

#### `number` parameter

This parameter allow an automatic numbering of the headings in the TOC and in files. Each level (# number) can have its own numbering scheme, each scheme being separated by a comma.

* `m` is the heading level concerned by the numbering
* <symbol> is a number (e.g: `1`) or a letter (e.g: `a`) for this level, case (`a` or `A`) is preserved and numbering starts with the given value
* `sep` is the symbol to use after this level numbering, e.g `.` or `-`

Any heading level above 9 will be ignored in the TOC.

### Example

```code
.TOC level=1-3 title=2,".fr((Table des matières.)).en((Table Of Contents))" number=1:A-,2:1.,3:1
```

This directive generates a TOC using the headings `#` to `###` found in each file.

The table title will be `## Table des Matières` in the `.fr.md` file, and `## Table Of Contents` for other languages.

Numbering scheme will be `A` to `Z` for level 1 headings, `A-1` for level 2, and `A-1.1` for level 3 headings.
