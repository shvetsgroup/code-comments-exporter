<?php namespace ShvetsGroup\CommentsExporter\Parsers;

use ShvetsGroup\CommentsExporter\Comment;

class BaseParser implements Parser
{
    /**
     * Parse source file for comments.
     * @param string $content
     * @param array $options
     * @return Comment[]
     */
    public function parse(string $content, array $options): array
    {
        return $this->tokenize($content, $options)['comments'];
    }

    /**
     * Update source file with new comments.
     * @param string $content
     * @param Comment[] $comments
     * @param array $options
     * @return string
     */
    public function update(string $content, array $comments, array $options): string
    {
        $tokenized = $options['tokenized'] ?? $this->tokenize($content, $options)['tokenized'];
        $wordWrap = $options['word-wrap-size'] ?? 0;

        $wrapFunction = function($text, $indent, $type) use ($wordWrap) {
            $comment_size = strlen($type);
            $width = $wordWrap - strlen($indent) - $comment_size;
            if ($width <= 0) {
                return $text;
            }
            return $this->mb_wordwrap($text, $width);
        };

        foreach ($comments as $comment) {
            $tokenized = preg_replace_callback('|^([\s]*)// ###' . $comment->getId() . '###|m', function ($matches) use ($comment, $wrapFunction) {
                $indent = $matches[1];
                $type = $comment->getType();
                $text = $comment->getComment();
                switch ($type) {
                    case 'simple':
                        $text = $wrapFunction($text, $indent, '// ');
                        $text = preg_replace('|^.*?$|m', $indent . '// $0', $text);
                        break;
                    case 'multiline':
                        $text = $wrapFunction($text, $indent, ' * ');
                        $asteriskStart = mb_substr($text, 0, 1) == '*';
                        $asteriskEnd = mb_substr($text, mb_strlen($text) - 1, 1) == '*';
                        $text = $indent . "/*" . ($asteriskStart ? '' : ' ')
                            . preg_replace('|\n|', "\n" . $indent . ' * ', $text)
                            . ($asteriskEnd ? '' : ' ') . "*/";
                        break;
                    case 'doc':
                        $text = $wrapFunction($text, $indent, ' * ');
                        $text = $indent . "/**\n"
                            . preg_replace('|^.*?$|m', $indent . ' * $0', $text)
                            . "\n" . $indent . " */";
                        break;
                }
                return $text;
            }, $tokenized);
        }

        return $tokenized;
    }

    /**
     * Process a source file, extract the comments and insert tokens numbered
     * tokens instead.
     * @param string $content
     * @param array $options
     * @return array
     */
    public function tokenize(string $content, array $options): array
    {
        $original = $content;
        $comments = [];

        $tokenized = preg_replace_callback('#(?:(?<!:)(//).*(?:\n *//.*)*|(/\*\*)(?:\n *\*.*)*\n *\*/|(/\*)[\s\S]+?\*/)#', function ($matches) use (&$comments, $options) {
            $fixWordWrap = $options['fix-word-wrap'] ?? false;

            $type = 'unknown';
            if ($matches[1]) {
                $type = 'simple';
            } else if (isset($matches[3]) && $matches[3]) {
                $type = 'multiline';
            } else if (isset($matches[2]) && $matches[2]) {
                $type = 'doc';
            }

            $comment = $matches[0];

            // Remove javadoc headers
            $comment = trim($comment);
            $comment = preg_replace('#/\*\*?#', "", $comment);
            $comment = preg_replace('#\*/#', "", $comment);
            $comment = trim($comment);

            // Cut off comment line headers (i.e. "//", " * ") headers
            if ($type == 'simple') {
                $comment = preg_replace('#^ *//#m', "", $comment);
            } else if ($type == 'multiline') {
                $comment = preg_replace('#^ *\*#m', "", $comment);
            } else if ($type == 'doc') {
                $comment = preg_replace('#^ *\* ?#m', "", $comment);
            }

            // Trim spaces at the end of lines.
            $comment = preg_replace('# +\n#', "\n", $comment);

            if ($fixWordWrap) {
                // Keep code blocks intact.
                $comment = preg_replace_callback('#(```\w*|@code)\n([\s\S]*?)(```|@endcode)#', function ($matches) {
                    $block = $matches[2];
                    $block = preg_replace('#^.*$#m', "###no###$0###wrap###", $block);
                    return "###no###{$matches[1]}###wrap###\n" . $block . "###no###{$matches[3]}###wrap###";
                }, $comment);

                // Keep the indented areas intact.
                $comment = preg_replace('#^(    +.*)$#m', "###no###$1###wrap###", $comment);

                // Trim spaces at the beginning of lines.
                $comment = preg_replace('#^ +#m', "", $comment);

                // Keep the standalone doc tags on new lines.
                $comment = preg_replace('#^(@\w+)$#m', "###no###$1###wrap###", $comment);
                // Keep the doc tags and list items from wrapping.
                $comment = preg_replace('#^([@\[\-\*\.].*)$#m', "###newline###$1", $comment);

                // Get rid of series of blank lines (!@ will be replaced with one blank line later).
                $comment = preg_replace('#\n\n+#', '!@', $comment);

                // And glue together the rest of lines.
                $comment = preg_replace('#\n#', ' ', $comment);

                // Restore blank lines.
                $comment = preg_replace('#!@#', "\n\n", $comment);
                $comment = trim($comment);

                // Restore areas that are not suppose to be word wrapped.
                $comment = preg_replace('/[\n ]?(###(no|newline)###)/', "\n$1", $comment);
                $comment = preg_replace('/(###wrap###)[\n ]?/', "$1\n", $comment);
                $comment = preg_replace('/###(no|wrap|newline)###/', "", $comment);
                $comment = trim($comment);
            }

//            $locale_tag_regexp = "(" . strtoupper(implode('|', $this->locales)) . "):";
//            if (preg_match("/(?=$locale_tag_regexp)/", $comment)) {
//                $raw_multilang_comments = preg_split("/(?=$locale_tag_regexp)/", $comment, -1, PREG_SPLIT_NO_EMPTY);
//                $raw_comments2 = [];
//                foreach ($raw_multilang_comments as $comment) {
//                    $locale = strtolower(substr($comment, 0, 2));
//                    $comment = trim(preg_replace("/^$locale_tag_regexp\s*/", '', $comment));
//                    $raw_comments2[$locale] = $comment;
//                }
//            } else {
//                $raw_comments2['en'] = $comment;
//            }

//            foreach ($this->locales as $locale) {
//                $multi_locale_comments[$locale] = $raw_comments2[$locale] ?? '';
//            }

            $id = count($comments);
            $comment = new Comment($id, $type, $comment);
            $comments[] = $comment;
            return '// ###' . $comment->getId() . '###';
        }, $content);

        return [
            'original' => $original,
            'tokenized' => $tokenized,
            'comments' => $comments
        ];
    }

    /**
     * Wraps any string to a given number of characters.
     *
     * This implementation is multi-byte aware and relies on {@link
     * http://www.php.net/manual/en/book.mbstring.php PHP's multibyte
     * string extension}.
     *
     * @see wordwrap()
     * @link https://api.drupal.org/api/drupal/core%21vendor%21zendframework%21zend-stdlib%21Zend%21Stdlib%21StringWrapper%21AbstractStringWrapper.php/function/AbstractStringWrapper%3A%3AwordWrap/8
     * @param string $string
     *   The input string.
     * @param int $width [optional]
     *   The number of characters at which <var>$string</var> will be
     *   wrapped. Defaults to <code>75</code>.
     * @param string $break [optional]
     *   The line is broken using the optional break parameter. Defaults
     *   to <code>"\n"</code>.
     * @param boolean $cut [optional]
     *   If the <var>$cut</var> is set to <code>TRUE</code>, the string is
     *   always wrapped at or before the specified <var>$width</var>. So if
     *   you have a word that is larger than the given <var>$width</var>, it
     *   is broken apart. Defaults to <code>FALSE</code>.
     * @return string
     *   Returns the given <var>$string</var> wrapped at the specified
     *   <var>$width</var>.
     */
    function mb_wordwrap($string, $width = 75, $break = "\n", $cut = false) {
        $string = (string) $string;
        if ($string === '') {
            return '';
        }

        $break = (string) $break;
        if ($break === '') {
            trigger_error('Break string cannot be empty', E_USER_ERROR);
        }

        $width = (int) $width;
        if ($width === 0 && $cut) {
            trigger_error('Cannot force cut when width is zero', E_USER_ERROR);
        }

        if (strlen($string) === mb_strlen($string)) {
            return wordwrap($string, $width, $break, $cut);
        }

        $stringWidth = mb_strlen($string);
        $breakWidth = mb_strlen($break);

        $result = '';
        $lastStart = $lastSpace = 0;

        for ($current = 0; $current < $stringWidth; $current++) {
            $char = mb_substr($string, $current, 1);

            $possibleBreak = $char;
            if ($breakWidth !== 1) {
                $possibleBreak = mb_substr($string, $current, $breakWidth);
            }

            if ($possibleBreak === $break) {
                $result .= mb_substr($string, $lastStart, $current - $lastStart + $breakWidth);
                $current += $breakWidth - 1;
                $lastStart = $lastSpace = $current + 1;
                continue;
            }

            if ($char === ' ') {
                if ($current - $lastStart >= $width) {
                    $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
                    $lastStart = $current + 1;
                }

                $lastSpace = $current;
                continue;
            }

            if ($current - $lastStart >= $width && $cut && $lastStart >= $lastSpace) {
                $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
                $lastStart = $lastSpace = $current;
                continue;
            }

            if ($current - $lastStart >= $width && $lastStart < $lastSpace) {
                $result .= mb_substr($string, $lastStart, $lastSpace - $lastStart) . $break;
                $lastStart = $lastSpace = $lastSpace + 1;
                continue;
            }
        }

        if ($lastStart !== $current) {
            $result .= mb_substr($string, $lastStart, $current - $lastStart);
        }

        return $result;
    }
}