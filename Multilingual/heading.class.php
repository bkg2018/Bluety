<?php

namespace MultilingualMarkdown {
    /**
     * Heading class, used by $headings array for all headings from all files.
     */
    class Heading
    {
        public $number = 0;     /// unique number over all files and headings
        public $text = '';      /// heading text, including MLMD directives if needed
        public $level = 0;      /// heading level = number of '#'s
        public $line = '';      /// line number in source file
        public $prefix = '';    /// heading prefix in TOC and text, computed
                                /// from 'numbering' directive or toc parameter
    }
}
