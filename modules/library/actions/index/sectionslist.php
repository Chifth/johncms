<?php

use Library\Utils;

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

$total = $db->query(
    'SELECT COUNT(*) FROM `library_cats` WHERE '
    . ($id !== null ? '`parent` = ' . $id : '`parent` = 0')
)->fetchColumn();

if ($total) {
    $req = $db->query(
        'SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE '
        . ($id !== null ? '`parent` = ' . $id : '`parent` = 0') . ' ORDER BY `pos` ASC LIMIT ' . $start . ', ' . $user->config->kmess
    );
}

$i = 0;

echo $view->render(
    'library::sectionslist',
    [
        'pagination' => $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess),
        'total'      => $total,
        'admin'      => $adm,
        'list'       =>
            static function () use ($req, $tools, $id, $i, $total) {
                while ($res = $req->fetch()) {
                    $i++;
                    $res['name'] = $tools->checkout($res['name']);
                    $res['libCounter'] = Utils::libCounter($res['id'], $res['dir']);
                    $res['description'] = $tools->checkout($res['description']);
                    $res['sectionListAdminPanel'] = Utils::sectionsListAdminPanel($id, $res['id'], $i, $total);
                    $res['sectionAdminPanel'] = Utils::sectionAdminPanel($id);

                    yield $res;
                }
            },
    ]
);
