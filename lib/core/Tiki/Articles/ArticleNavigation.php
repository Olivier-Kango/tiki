<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Articles;

/** Readable neighbours in publication order, without loading the entire archive. */
final class ArticleNavigation
{
    public static function forArticle(array $article, $artlib, int $now): array
    {
        if (
            empty($article['articleId']) || ($article['ispublished'] ?? 'n') !== 'y'
            || (int) $article['publishDate'] > $now
        ) {
            return [];
        }
        $neighbors = [];
        foreach (['previous' => '>', 'next' => '<'] as $direction => $operator) {
            $date = (int) $article['publishDate'];
            $id = (int) $article['articleId'];
            $order = $operator === '>' ? 'ASC' : 'DESC';
            do {
                $candidates = $artlib->fetchAll(
                    "SELECT `articleId`, `publishDate` FROM `tiki_articles`
                    WHERE `ispublished` = 'y' AND COALESCE(`lang`, '') = ? AND `publishDate` <= ?
                    AND (`publishDate` $operator ? OR (`publishDate` = ? AND `articleId` $operator ?))
                    ORDER BY `publishDate` $order, `articleId` $order",
                    [(string) ($article['lang'] ?? ''), $now, $date, $date, $id],
                    50,
                    0
                );
                if (! $candidates) {
                    break;
                }
                // Native listing applies object permissions, category jail and visibility.
                $visible = $artlib->list_articles(
                    maxRecords: 50,
                    lang: (string) ($article['lang'] ?? ''),
                    ispublished: 'y',
                    filter: ['articleId' => array_column($candidates, 'articleId')]
                );
                $readable = [];
                foreach ($visible['data'] as $row) {
                    if (
                        ($row['disp_article'] ?? 'n') === 'y'
                        && ($row['perms']['tiki_p_read_article'] ?? 'n') === 'y'
                    ) {
                        $readable[(int) $row['articleId']] = $row;
                    }
                }
                foreach ($candidates as $candidate) {
                    if (isset($readable[(int) $candidate['articleId']])) {
                        $row = $readable[(int) $candidate['articleId']];
                        $neighbors[$direction] = ['articleId' => $row['articleId'], 'title' => $row['title']];
                        break 2;
                    }
                }
                $last = end($candidates);
                $date = (int) $last['publishDate'];
                $id = (int) $last['articleId'];
            } while (count($candidates) === 50);
        }
        return $neighbors;
    }
}
