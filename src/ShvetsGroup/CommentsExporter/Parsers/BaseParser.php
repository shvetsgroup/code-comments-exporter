<?php namespace ShvetsGroup\CommentsExporter\Parsers;

use ShvetsGroup\CommentsExporter\Comment;

class BaseParser implements Parser
{
    public function parse($content, array $options): array
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
}