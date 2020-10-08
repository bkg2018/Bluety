# Directives et tokens

| Directive ou token | Classe                 | processInput            | outputNow                 | output                  |
|--------------------|------------------------|-------------------------|---------------------------|-------------------------|
| .languages         | TokenLanguages         | ignorer jusqu'à la fin de la ligne (directive traitée par le preprocessing) | true | rien à envoyer |
| .numbering         | TokenNumbering         | ignorer jusqu'à la fin de la ligne (directive traitée par le preprocessing)| true | modifier le numbering en cours ($filer ?) |
| .toc               | TokenTOC               | traiter les paramètres de la toc | true | envoyer le contenu de la toc |
| ```                | TokenTripleBacktick    | | | |
| ``                 | TokenDoubleBacktick    | | | |
| `                  | TokenSingleBacktick    | | | |
| "                  | TokenDoubleQuote       | | | |
| <    >             | TokenSpaceEscape       | | | |
| ```code            | TokenFence             | | | |
| # heading          | TokenHeading           | | | |
| .all               | TokenOpenAll           | | | |
| .((                | TokenOpenDefault       | | | |
| .ignore((          | TokenOpenIgnore        | | | |
| .<code>((          | TokenOpenLanguage      | | | |
| .))                | TokenClose             | | | |
| text               | TokenText              | | | |
