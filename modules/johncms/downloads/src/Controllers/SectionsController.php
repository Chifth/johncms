<?php

declare(strict_types=1);

namespace Johncms\Downloads\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Downloads\DownloadsCounters;
use Johncms\Downloads\DownloadsUtils;
use Johncms\Downloads\Models\DownloadsFile;
use Johncms\Downloads\Models\DownloadsSection;
use Johncms\Downloads\Resources\TopicResource;
use Johncms\Downloads\Services\DownloadsTopicService;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Utility\Numbers;

class SectionsController extends BaseDownloadsController
{
    public function index(Session $session, DownloadsCounters $downloadsCounters): string
    {
        // Downloads categories
        $sections = (new DownloadsSection())
            ->withCount('subsections', 'topics')
            ->with('subsections')
            ->where('parent', '=', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();

        $session->remove(['fsort_id', 'fsort_users']);

        $downloadsSettings = config('downloads.settings');
        $this->metaTagManager->setKeywords($downloadsSettings['downloads_keywords']);
        $this->metaTagManager->setDescription($downloadsSettings['downloads_description']);

        return $this->render->render(
            'johncms/downloads::index',
            [
                'sections'     => $sections,
                'online'       => [
                    'users'  => $downloadsCounters->onlineUsers(),
                    'guests' => $downloadsCounters->onlineGuests(),
                ],
                'files_count'  => $downloadsSettings['file_counters'] ? Numbers::formatNumber((new DownloadsFile())->count()) : 0,
                'unread_count' => Numbers::formatNumber($downloadsCounters->unreadMessages()),
            ]
        );
    }

    public function show(
        int $id,
        Session $session,
        DownloadsCounters $downloadsCounters,
        DownloadsTopicService $topicRepository,
        ?User $user,
        DownloadsUtils $downloadsUtils,
    ): string {
        $downloadsSettings = config('downloads.settings');
        try {
            $currentSection = DownloadsSection::query()
                ->when($downloadsSettings['file_counters'], function (Builder $builder) {
                    return $builder->withCount('categoryFiles');
                })
                ->findOrFail($id);
        } catch (ModelNotFoundException) {
            DownloadsUtils::notFound();
        }

        $session->remove(['fsort_id', 'fsort_users']);

        // Build breadcrumbs
        $downloadsUtils->buildBreadcrumbs($currentSection->parent, $currentSection->name);

        $this->metaTagManager->setTitle($currentSection->name);
        $this->metaTagManager->setPageTitle($currentSection->name);
        $this->metaTagManager->setKeywords($currentSection->calculated_meta_keywords);
        $this->metaTagManager->setDescription($currentSection->calculated_meta_description);

        $templateBaseData = [
            'id'           => $currentSection->id,
            'online'       => [
                'users'  => $downloadsCounters->onlineUsers(true),
                'guests' => $downloadsCounters->onlineGuests(true),
            ],
            'files_count'  => $downloadsSettings['file_counters'] ? Numbers::formatNumber($currentSection->category_files_count) : 0,
            'unread_count' => Numbers::formatNumber($downloadsCounters->unreadMessages()),
        ];

        // If the section contains topics, then show a list of topics
        if ($currentSection->section_type) {
            $topics = $topicRepository->getTopics($id)->paginate();
            $resource = TopicResource::createFromCollection($topics);

            // Access to create topics
            $createAccess = false;
            if (($user && ! $user->hasBan(['downloads_read_only', 'downloads_create_topics'])) || $user?->hasAnyRole()) {
                $createAccess = true;
            }

            return $this->render->render(
                'johncms/downloads::topics',
                array_merge(
                    $templateBaseData,
                    [
                        'pagination'     => $topics->render(),
                        'create_access'  => $createAccess,
                        'createTopicUrl' => route('downloads.newTopic', ['sectionId' => $id]),
                        'topics'         => $resource->getItems(),
                        'total'          => $topics->total(),
                    ]
                )
            );
        } else {
            // List of downloads sections
            $sections = (new DownloadsSection())
                ->withCount(['subsections', 'topics'])
                ->where('parent', '=', $id)
                ->orderBy('sort')
                ->get();

            return $this->render->render(
                'johncms/downloads::section',
                array_merge(
                    $templateBaseData,
                    [
                        'sections' => $sections,
                        'total'    => $sections->count(),
                    ]
                )
            );
        }
    }
}
