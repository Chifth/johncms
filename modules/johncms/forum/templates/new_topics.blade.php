<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * @var $title
 * @var $page_title
 * @var $sections
 * @var $online
 * @var $files_count
 * @var $unread_count
 * @var $create_access
 * @var $pagination
 * @var $empty_message
 * @var $is_unread
 */
?>
@extends('system::layout/default')

@section('content')
    <?php if (! empty($show_period)): ?>
    <div class="row">
        <form action="<?= route('forum.period') ?>" method="get" class="col-md-4">
            <div class="input-group mb-3">
                <input type="text" class="form-control" maxlength="3" name="period"
                       value="<?= $current_period ?>"
                       placeholder="<?= __('Period') ?>"
                       aria-label="<?= __('Period') ?>">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><?= __('Show period') ?></button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($topics)): ?>
        @include('system::app/alert',
            [
                'alert_type' => 'alert-info',
                'alert'      => $empty_message,
            ])
    <?php endif; ?>

    <?php foreach ($topics as $topic): ?>
    <div class="forum-section post-item <?= $topic['deleted'] ? ' deleted-post' : '' ?><?= $topic['closed'] ? ' closed-topic' : '' ?>">
        <div class="section-header">
            <div class="d-flex align-items-center w-100">
                    <?php if ($topic['has_icons']): ?>
                <div class="topic-icons me-1 d-flex align-items-center">
                        <?php if ($topic['pinned']): ?>
                    <div class="me-1" title="<?= __('Pinned topic') ?>">
                        <svg class="icon">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#pin"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                        <?php if ($topic['has_poll']): ?>
                    <div class="me-1" title="<?= __('Topic has poll') ?>">
                        <svg class="icon">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#bar-chart"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                        <?php if ($topic['closed']): ?>
                    <div class="me-1" title="<?= __('Closed topic') ?>">
                        <svg class="icon">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#lock"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                        <?php if ($topic['deleted']): ?>
                    <div class="me-1" title="<?= __('Deleted topic') ?>">
                        <svg class="icon">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#x"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <a href="<?= $topic['last_page_url'] ?>" class="text-dark-brown flex-grow-1 flex-md-grow-0"><?= $topic['name'] ?></a>
                <span class="badge rounded-pill bg-light text-primary border ms-3"><?= $topic['post_count'] ?></span>
            </div>
        </div>
        <div class="small pt-2 text-muted">
                <?= __('Author') ?>: <?= $topic['user_name'] ?>,
                <?= __('Last post') ?>: <?= $topic['last_post_date'] ?>, <?= $topic['last_post_author'] ?>
        </div>
            <?php if (! empty($topic['forum_url']) && ! empty($topic['section_url'])): ?>
        <div class="small pt-1">
            <a href="<?= $topic['forum_url'] ?>"><?= $topic['forum_name'] ?></a> / <a href="<?= $topic['section_url'] ?>"><?= $topic['section_name'] ?></a>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="mt-3">
        <div><?= __('Total') ?>: <?= $total ?></div>
        <!-- Page switching -->
        <?php if ($pagination): ?>
        <div class="mt-3"><?= $pagination ?></div>
        <?php endif ?>
    </div>

    <div class="mt-3">
        <?php if (! empty($mark_as_read) && ! empty($total)): ?>
        <div>
            <a href="<?= $mark_as_read ?>"><?= __('Mark as read') ?></a>
        </div>
        <?php endif; ?>
        <div>
            <a href="/forum/"><?= __('Forum') ?></a>
        </div>
    </div>
@endsection