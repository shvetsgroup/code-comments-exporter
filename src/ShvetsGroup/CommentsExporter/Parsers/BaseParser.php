<?php namespace ShvetsGroup\CommentsExporter\Parsers;

use ShvetsGroup\CommentsExporter\Comment;

class BaseParser implements Parser
{
    public function parse($content): array
    {
        $original = $content;
        $comments = [];

        $tokenized = preg_replace_callback('#(?:(?<!:)(//).*(?:\n *//.*)*|(/\*)[\s\S]+?\*/|(/\*\*)(?:\n *\*.*)*\n *\*/)#', function ($matches) use (&$comments) {
            $comment = $matches[0];

            // Remove extra spacing from start
            $comment = preg_replace('#^\n? *#', "", $comment);

            // Remove javadoc headers
            $comment = preg_replace('#/\*\*?#', "", $comment);
            $comment = preg_replace('#\*/#', "", $comment);
            $comment = trim($comment);

            // Remove // headers
            $comment = preg_replace('#^ *// *#m', "", $comment);

            // Remove * headers
            $comment = preg_replace('#^ *\* *#m', "", $comment);

            // Cleanup comment
            $comment = preg_replace('# +\n#', "\n", $comment);
            $comment = preg_replace('#\n +#', "\n", $comment);
            $comment = preg_replace('#\n\n+#', '!@', $comment);
            $comment = preg_replace('#\n(?! *\.\.\.)#', ' ', $comment);
            $comment = preg_replace('#!@#', "\n\n", $comment);
            $comment = trim($comment);

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

            $type = 'unknown';
            if ($matches[1]) {
                $type = 'simple';
            } else if ($matches[2]) {
                $type = 'javadoc';
            }

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