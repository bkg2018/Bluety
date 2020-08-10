# Output formats and numbering

## Output modes

- html      : html `<A>` anchors ("id") before headings, html `<A href>` links in TOC, `text` or `<numbered> text` in headings
- htmlold   : html `<A>` anchors ("name") before headings, html `<A href>` links in TOC, `text` or `<numbered> text` in headings
- md        : html `<A>` anchors ("id") before headings, MD `[](#)` links in TOC, `text` or `<numbered> text` in headings
- mdpure    : no anchors, MD `[](#)` *automatic links* in TOC, `text` or `1. text` in headings

## Numbering

Level 1 headings: numbering can specify a prefix which would be added to level 1 eading only, like 'Chapter' or 'Part' and a specific
termionator to separate the prefix + numbering from the text.

levels 2 and above headings only feature numbering, which is always temrinated by `) ` before the text.

### Definitions syntaxes

Level 1 def:        `[<prefix>]:<symbol><separator>`
Level 2-9 def:      `:<symbol><separator>`
Numbering syntax:   `<level:><def>[,...]`

### Level 1 example

```code
    .numbering 1:.((Chapter .)).fr((Chapitre .)):X-
```

Will generate in 'en' file:

```code
    # Chapter I - *file title*
```

And in 'fr' file:

```code
    # Chapitre I - *titre fichier*
```

Level 2 to 4 example:

```code
    .numbering 2::A-,3::1,4::1
```

Generates in files:

```code
    ## A) First part
    
    ### A-1) First sub-part of A

    #### A-1.1) First sub sub-part

    #### A-1.2) Second sub sub-part

    ## B) Second part

    ### B-1) First sub-part of B
```


| ***Element***                 | html        | htmlold     | md          | html+num    | htmlold+num | md+num      | mdpure      |
:-------------------------------|-------------|-------------|-------------|-------------|-------------|-------------|-------------|
***Heading Anchor:***           |             |             |             |             |             |             |             |
   `<a name="id">\<br>`         |      -      |      o      |      -      |      -      |      o      |      -      |      -      |
   `<a id="id">\<br>`           |      o      |      -      |      o      |      o      |      -      |      o      |      -      |
   N/A                          |      -      |      -      |      -      |      -      |      -      |      -      |      o      |
***TOC link to heading:***      |             |             |             |             |             |             |             |
   `<a href="file#id">`         |      o      |      o      |      -      |      o      |      o      |      -      |      -      |
   `[text](#id)`                |      -      |      -      |      o      |      -      |      -      |      o      |      -      |
   `[text](#autoid)`            |      -      |      -      |      -      |      -      |      -      |      -      |      o      |
***TOC heading title:***        |             |             |             |             |             |             |             |
   `<num>) text`                |      -      |      -      |      -      |      o      |      o      |      o      |      -      |
   `\- text`                    |      o      |      o      |      o      |      -      |      -      |      -      |      -      |
   `1. text`                    |      -      |      -      |      -      |      -      |      -      |      -      |      o      |
***Heading title:***            |             |             |             |             |             |             |             |
   `<num>) text`                |      -      |      -      |      -      |      o      |      o      |      o      |      -      |
   `text`                       |      o      |      o      |      o      |      -      |      -      |      -      |      -      |
   `1. text`                    |      -      |      -      |      -      |      -      |      -      |      -      |      o      |
***TOC heading space:***        |             |             |             |             |             |             |             |
   `4 x &nbsp; \* level`        |      o      |      o      |      -      |      o      |      o      |      -      |      -      |
   `2 x <space> \* level`       |      -      |      -      |      o      |      -      |      -      |      o      |      -      |
   `3 x <space> \* level`       |      -      |      -      |      -      |      -      |      -      |      -      |      o      |


