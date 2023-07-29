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

use Illuminate\Database\Eloquent\Relations\HasMany;

trait SectionRelations
{
    /**
     * Relation to subsections
     */
    public function subsections(): HasMany
    {
        return $this->hasMany(self::class, 'parent', 'id');
    }

    /**
     * Relation to topics
     */
    public function topics(): HasMany
    {
        return $this->hasMany(DownloadsTopic::class, 'section_id', 'id');
    }

    /**
     * Relation to files of section
     */
    public function sectionFiles(): HasMany
    {
        return $this->hasMany(DownloadsFile::class, 'subcat', 'id');
    }

    /**
     * Relation to files of category
     */
    public function categoryFiles(): HasMany
    {
        return $this->hasMany(DownloadsFile::class, 'cat', 'id');
    }
}
