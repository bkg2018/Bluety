# Directives et tokens

Token classes hierarchy:

Token +--- TokenBaseKeyworded +--- TokenBaseEscaper +--- TokenEscaperDoubleBacktick
      |                       |                     +--- TokenEscaperSingleBacktick
      |                       |                     +--- TokenEscaperTripleBacktick
      |                       |                     +--- TokenEscaperDoubleQuote
      |                       |                     +--- TokenEscaperMLMD
      |                       |                     +--- TokenEscaperFence
      |                       |
      |                       +--- TokenBaseInline  +--- TokenClose
      |                       |                     +--- TokenOpenLanguage  +--- TokenOpenAll
      |                       |                                             +--- TokenOpenDefault
      |                       |                                             +--- TokenOpenIgnore
      |                       |
      |                       +--- TokenBaseSingleLine  +--- TokenHeading
      |                                                 +--- TokenLanguages
                                                        +--- TokenNumbering
                                                        +--- TokenTOC
                                                        +--- TokenTopNumber
      |                       +--- TokenEmptyLine  +---
      |                       +--- TokenEOL  +---
      +--- TokenText
