<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

// use Johncms\News\Controllers\Admin\AdminArticleController;
// use Johncms\News\Controllers\Admin\AdminController;
// use Johncms\News\Controllers\Admin\AdminSectionController;
use Johncms\Downloads\Controllers\BaseDownloadsController;
// use Johncms\News\Controllers\CommentsController;
// use Johncms\News\Controllers\SearchController;
// use Johncms\News\Controllers\SectionController;
// use Johncms\News\Controllers\VoteController;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasRoleMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    // $router->addPatternMatcher('downloadsSlug', '[\w.+-]+');
    // $router->addPatternMatcher('sectionPath', '[\w/+-]+');

    $router->get('/downloads[/]', [BaseDownloadsController::class, 'index'])->setName('downloads.index');
    $router->get('/downloads/{id:number}[/]', [SectionsController::class, 'show'])->setName('downloads.section');

    // $router->group('/admin/news', function (RouteGroup $route) {
    //     $route->get('/', [AdminController::class, 'index'])->setName('news.admin.index');
    //     $route->get('/content/[{section_id:number}[/]]', [AdminController::class, 'section'])->setName('news.admin.section');
    //     $route->get('/settings[/]', [AdminController::class, 'settings'])->setName('news.admin.settings');
    //     $route->post('/settings[/]', [AdminController::class, 'settings'])->setName('news.admin.settingsStore');

    //     // Articles
    //     $route->get('/edit_article/{article_id:number}[/]', [AdminArticleController::class, 'edit'])->setName('news.admin.article.edit');
    //     $route->post('/edit_article/{article_id:number}[/]', [AdminArticleController::class, 'edit'])->setName('news.admin.article.editStore');
    //     $route->get('/add_article/[{section_id:number}[/]]', [AdminArticleController::class, 'add'])->setName('news.admin.article.add');
    //     $route->post('/add_article/[{section_id:number}[/]]', [AdminArticleController::class, 'add'])->setName('news.admin.article.addStore');
    //     $route->get('/del_article/{article_id:number}[/]', [AdminArticleController::class, 'del'])->setName('news.admin.article.del');
    //     $route->post('/del_article/{article_id:number}[/]', [AdminArticleController::class, 'del'])->setName('news.admin.article.delStore');

    //     // Sections
    //     $route->get('/add_section/[{section_id:number}[/]]', [AdminSectionController::class, 'add'])->setName('news.admin.sections.add');
    //     $route->post('/add_section/[{section_id:number}[/]]', [AdminSectionController::class, 'add'])->setName('news.admin.sections.add_store');
    //     $route->get('/edit_section/{section_id:number}[/]', [AdminSectionController::class, 'edit'])->setName('news.admin.sections.edit');
    //     $route->post('/edit_section/{section_id:number}[/]', [AdminSectionController::class, 'edit'])->setName('news.admin.sections.edit_store');
    //     $route->get('/del_section/{section_id:number}[/]', [AdminSectionController::class, 'del'])->setName('news.admin.sections.del');
    //     $route->post('/del_section/{section_id:number}[/]', [AdminSectionController::class, 'del'])->setName('news.admin.sections.del_store');

    //     // File uploader
    //     $route->post('/upload_file[/]', [AdminArticleController::class, 'loadFile'])->setName('news.admin.sections.loadFile');
    // })->middleware(new HasRoleMiddleware('admin'));
};
