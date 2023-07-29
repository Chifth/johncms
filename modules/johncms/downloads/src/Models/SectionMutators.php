<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Downloads\Models;

use Illuminate\Support\Str;

/**
 * Trait SectionMutators
 *
 * @package Downloads\Models
 * @property string $calculated_meta_description
 * @property string $calculated_meta_keywords
 */
trait SectionMutators
{
    /**
     * Ссылка на страницу просмотра раздела
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return route('downloads.section', [
            'id'          => $this->id,
            'sectionName' => Str::slug($this->name),
        ]);
    }

    /**
     * Section meta description
     *
     * @return string
     */
    public function getCalculatedMetaDescriptionAttribute(): string
    {
        if (! empty($this->meta_description)) {
            return $this->meta_description;
        }

        $template = config('downloads.settings.section_description', '');
        return trim(
            str_replace(
                [
                    '#name#',
                    '#description#',
                ],
                [
                    $this->name,
                    strip_tags($this->description),
                ],
                $template
            )
        );
    }

    /**
     * Section meta keywords
     *
     * @return string
     */
    public function getCalculatedMetaKeywordsAttribute(): string
    {
        if (! empty($this->meta_keywords)) {
            return $this->meta_keywords;
        }

        $template = config('downloads.settings.section_keywords', '');
        return trim(
            str_replace(
                [
                    '#name#',
                    '#description#',
                ],
                [
                    $this->name,
                    strip_tags($this->description),
                ],
                $template
            )
        );
    }
}
