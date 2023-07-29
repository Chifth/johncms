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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Johncms\Database\Eloquent\Casts\Serialize;
use Johncms\Downloads\DownloadsPermissions;
use Johncms\Settings\SiteSettings;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Class Topic
 *
 * @package Downloads\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $section_id
 * @property string $name
 * @property string $description
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $view_count
 * @property int $user_id
 * @property string $user_name
 * @property Carbon $created_at
 * @property int $post_count
 * @property int $mod_post_count
 * @property int $last_post_date
 * @property int $last_post_author
 * @property string $last_post_author_name
 * @property int $last_message_id
 * @property int $mod_last_post_date
 * @property int $mod_last_post_author
 * @property string $mod_last_post_author_name
 * @property int $mod_last_message_id
 * @property bool $closed
 * @property string $closed_by
 * @property bool $deleted
 * @property string $deleted_by
 * @property array $curators
 * @property bool $pinned
 * @property bool $has_poll
 * @property int $old_id - Устаревшее.
 *
 * @property DownloadsSection $section
 * @method Builder read() - Метка прочитанного
 * @property string $url
 * @property string $show_posts_count
 * @property string $show_last_author
 * @property string $show_last_post_date
 * @property bool $has_icons
 * @property string $last_page_url
 * @property bool $unread
 * @property int $read
 * @property string $formatted_view_count
 *
 * @property int $files_count
 * @property DownloadsFile $files
 *
 * @property User $current_user
 * @property Tools $tools
 */
class DownloadsTopic extends Model
{
    use TopicMutators;

    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'downloads_topic';

    public $timestamps = false;

    protected $casts = [
        'closed'     => 'boolean',
        'deleted'    => 'boolean',
        'pinned'     => 'boolean',
        'has_poll'   => 'boolean',
        'view_count' => 'integer',
        'curators'   => Serialize::class,
    ];

    protected $appends = [
        'url',
        'show_posts_count',
        'show_last_author',
        'show_last_post_date',
        'has_icons',
        'last_page_url',
        'unread',
    ];

    protected $fillable = [
        'section_id',
        'name',
        'description',
        'meta_description',
        'meta_keywords',
        'view_count',
        'user_id',
        'user_name',
        'created_at',
        'post_count',
        'mod_post_count',
        'last_post_date',
        'last_post_author',
        'last_post_author_name',
        'last_message_id',
        'mod_last_post_date',
        'mod_last_post_author',
        'mod_last_post_author_name',
        'mod_last_message_id',
        'closed',
        'closed_by',
        'deleted',
        'deleted_by',
        'curators',
        'pinned',
        'has_poll',
    ];

    /**
     * Текущий пользователь
     *
     * @var User
     */
    protected $current_user;

    /**
     * @var Tools
     */
    protected $tools;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->current_user = di(User::class);
        $this->tools = di(Tools::class);
        $this->perPage = di(SiteSettings::class)->getPerPage();
    }

    /**
     * Добавляем в выборку количество непрочитанных сообщений с момента последнего прочтения темы.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRead(Builder $query): Builder
    {
        $user = di(User::class);
        if ($user) {
            return $query->selectSub(
                (new DownloadsUnread())
                    ->selectRaw('count(*)')
                    ->whereRaw('downloads_read.time >= downloads_topic.last_post_date')
                    ->whereRaw('downloads_read.topic_id = downloads_topic.id')
                    ->where('user_id', '=', $user->id),
                'read'
            )
                ->addSelect('downloads_topic.*');
        }
        return $query;
    }

    public function scopeWithoutDeleted(Builder $query): Builder
    {
        return $query->where('deleted', '!=', 1)->orWhereNull('deleted');
    }

    public function scopeWithoutDeletedForUsers(Builder $query): Builder
    {
        $user = di(User::class);
        if (! $user?->hasPermission(DownloadsPermissions::MANAGE_TOPICS)) {
            return $query->withoutDeleted();
        }
        return $query;
    }

    /**
     * Scope for unread, latest, period pages
     */
    public function scopeForLatest(Builder $builder)
    {
        $user = di(User::class);
        return $builder->select(['sect.name as section_name', 'downloads.name as downloads_name', 'downloads.id as downloads_id', 'downloads_topic.*'])
            ->leftJoin('downloads_sections as sect', 'sect.id', '=', 'downloads_topic.section_id')
            ->leftJoin('downloads_sections as downloads', 'downloads.id', '=', 'sect.parent')
            ->when(! $user?->hasAnyRole(), function (Builder $builder) {
                return $builder->where(fn($builder) => $builder->where('downloads_topic.deleted', '<>', 1)->orWhereNull('downloads_topic.deleted'));
            });
    }

    /**
     * Unread messages
     */
    public function scopeUnread(Builder $builder)
    {
        $user = di(User::class);
        return $builder->forLatest()
            ->leftJoin('downloads_read as rdm', function (JoinClause $joinClause) use ($user) {
                return $joinClause->on('downloads_topic.id', '=', 'rdm.topic_id')
                    ->where('rdm.user_id', $user->id);
            })
            ->where(function (Builder $builder) {
                return $builder->whereNull('rdm.topic_id')->orWhereRaw('`downloads_topic`.`last_post_date` > `rdm`.`time`');
            });
    }

    /**
     * Latest topics for period
     */
    public function scopePeriod(Builder $builder, int $period)
    {
        return $builder->forLatest()->where('downloads_topic.last_post_date', '>', $period);
    }

    /**
     * Связь с родительским разделом
     *
     * @return HasOne
     */
    public function section(): HasOne
    {
        return $this->hasOne(DownloadsSection::class, 'id', 'section_id');
    }

    /**
     * Relation to files of the topic
     */
    public function files(): HasMany
    {
        return $this->hasMany(DownloadsFile::class, 'topic', 'id');
    }
}
