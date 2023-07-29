<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Downloads\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Users\User;
use Throwable;

class BaseDownloadsController extends BaseController
{
    protected string $moduleName = 'johncms/downloads';

    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $pageTitle = __('Downloads');
        $this->navChain->add($pageTitle, route('downloads.index'));
        $this->metaTagManager->setAll($pageTitle);

        $config = di('config')['johncms'];
        $user = di(User::class);

        if (! $config['mod_down'] && ! $user?->hasAnyRole()) {
            $error = __('Forum is closed');
        } elseif ($config['mod_down'] === 1 && ! $user) {
            $error = __('For registered users only');
        }
    }
}
