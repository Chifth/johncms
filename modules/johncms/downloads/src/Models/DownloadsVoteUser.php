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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Users\User;

/**
 * Class DownloadsVoteUser
 *
 * @package Downloads\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $user
 * @property int $topic
 * @property int $vote
 */
class DownloadsVoteUser extends Model
{
    protected $table = 'downloads_vote_users';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'topic',
        'vote',
    ];

    public function userData(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user');
    }
}
