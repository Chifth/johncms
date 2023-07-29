<?php

declare(strict_types=1);

namespace Johncms\Downloads\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Johncms\Downloads\DownloadsPermissions;
use Johncms\Downloads\Models\DownloadsFile;
use Johncms\Downloads\Models\DownloadsMessage;
use Johncms\Downloads\Models\DownloadsSection;
use Johncms\Downloads\Models\DownloadsTopic;
use Johncms\Downloads\Models\DownloadsUnread;
use Johncms\Downloads\Models\DownloadsVote;
use Johncms\Downloads\Models\DownloadsVoteUser;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Users\UserManager;

class DownloadsTopicService
{
    public ?User $user;
    public Request $request;

    public function __construct(?User $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }

    public function getTopics(?int $sectionId = null): ?Builder
    {
        return DownloadsTopic::query()
            ->read()
            ->when(! $this->user?->hasPermission(DownloadsPermissions::MANAGE_TOPICS), function (Builder $builder) {
                /** @var DownloadsTopic $builder */
                return $builder->withoutDeleted();
            })
            ->when($sectionId, function (Builder $builder) use ($sectionId) {
                return $builder->where('section_id', '=', $sectionId);
            })
            ->orderByDesc('pinned')
            ->orderByDesc('last_post_date');
    }

    /**
     * Marking a topic as read for a specific user
     */
    public function markAsRead(int $topicId, int $userId, ?int $time = null): void
    {
        DownloadsUnread::query()->updateOrInsert(['topic_id' => $topicId, 'user_id' => $userId], ['time' => $time ?? time()]);
    }

    /**
     * Increase view counter and mark topic as viewed for current user's session
     */
    public function markAsViewed(DownloadsTopic $downloadsTopic): void
    {
        $session = di(Session::class);
        // Increasing the number of views
        if (empty($session->get('viewed_topics')) || ! in_array($downloadsTopic->id, $session->get('viewed_topics', []))) {
            $downloadsTopic->update(['view_count' => $downloadsTopic->view_count + 1]);
            $viewed = $session->get('viewed_topics', []);
            $viewed[] = $downloadsTopic->id;
            $session->set('viewed_topics', $viewed);
        }
    }

    /**
     * @param DownloadsSection $downloadsSection
     * @param User $user
     * @param array{
     *     name: string,
     *     message: string,
     *     meta_keywords: string | null,
     *     meta_description: string | null
     * } $fields
     * @return array{topic: DownloadsTopic, message: DownloadsMessage}
     */
    public function createTopic(DownloadsSection $downloadsSection, User $user, array $fields): array
    {
        $topic = DownloadsTopic::query()->create(
            [
                'section_id'       => $downloadsSection->id,
                'created_at'       => Carbon::now(),
                'user_id'          => $this->user->id,
                'user_name'        => $this->user->display_name,
                'name'             => $fields['name'],
                'meta_keywords'    => $fields['meta_keywords'] ?? null,
                'meta_description' => $fields['meta_description'] ?? null,
                'last_post_date'   => time(),
                'post_count'       => 0,
                'curators'         => $downloadsSection->access === 1 ? [$this->user->id => $this->user->display_name] : [],
            ]
        );

        $message = (new DownloadsMessage())->create(
            [
                'topic_id'     => $topic->id,
                'date'         => time(),
                'user_id'      => $this->user->id,
                'user_name'    => $this->user->display_name,
                'ip'           => $this->request->getIp(),
                'ip_via_proxy' => $this->request->getIpViaProxy(),
                'user_agent'   => $this->request->getUserAgent(),
                'text'         => $fields['message'],
            ]
        );

        // TODO: Replace it
        $tools = di(Tools::class);
        $tools->recountForumTopic($topic->id);

        // Update user activity
        $userManager = di(UserManager::class);
        $userManager->incrementActivity($user, 'downloads_posts');

        $this->markAsRead($topic->id, $user->id);

        return [
            'topic'   => $topic,
            'message' => $message,
        ];
    }

    /**
     * Update the topic fields
     */
    public function update(int | DownloadsTopic $topic, array $fields): DownloadsTopic
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update($fields);
        return $topic;
    }

    /**
     * Completely delete the topic and all related data
     */
    public function delete(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }

        DB::transaction(function () use ($topic) {
            $files = DownloadsFile::query()->where('topic', $topic->id)->get();
            if ($files->count() > 0) {
                foreach ($files as $file) {
                    unlink(UPLOAD_PATH . 'downloads/attach/' . $file->filename);
                    $file->delete();
                }
            }

            $topic->delete();
            (new DownloadsMessage())->where('topic_id', $topic->id)->delete();
            (new DownloadsVote())->where('topic', $topic->id)->delete();
            (new DownloadsVoteUser())->where('topic', $topic->id)->delete();
            (new DownloadsUnread())->where('topic_id', $topic->id)->delete();
        });
    }

    /**
     * Mark the topic as hidden
     */
    public function hide(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        DB::transaction(function () use ($topic) {
            $topic->update(['deleted' => true, 'deleted_by' => $this->user?->display_name]);
            (new DownloadsFile())->where('topic', $topic->id)->update(['del' => 1]);
        });
    }

    /**
     * Restore the topic
     */
    public function restore(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update(['deleted' => null, 'deleted_by' => $this->user?->display_name]);
    }

    /**
     * Mark the topic as closed
     */
    public function close(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update(['closed' => true, 'closed_by' => $this->user->display_name]);
    }

    /**
     * Open the topic
     */
    public function open(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update(['closed' => null, 'closed_by' => null]);
    }

    public function pin(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update(['pinned' => true]);
    }

    public function unpin(int | DownloadsTopic $topic): void
    {
        if (is_int($topic)) {
            $topic = DownloadsTopic::query()->findOrFail($topic);
        }
        $topic->update(['pinned' => null]);
    }
}
