<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

if ($user->rights >= 7 && $user->rights > $foundUser->rights) {
    // Сброс настроек пользователя
    $title = __('Reset user settings');
    $nav_chain->add(__('Profile'), '?user=' . $foundUser->id);
    $nav_chain->add($title);

    (new User())->find($foundUser->id)
        ->update(
            [
                'set_user'  => [],
                'set_forum' => [],
            ]
        );

    echo $view->render(
        'system::pages/result',
        [
            'title'    => $title,
            'type'     => 'alert-success',
            'message'  => sprintf(__('For user %s default settings were set.'), $foundUser->name),
            'back_url' => '?user=' . $foundUser->id,
        ]
    );
}
