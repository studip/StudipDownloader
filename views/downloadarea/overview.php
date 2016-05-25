<div style="font-size: 1.6em; text-align: center;">
    <? $latest_stable = array_shift($releases) ?>

    <a href="<?= PluginEngine::getLink($plugin, array(), "download/".$latest_stable['name']) ?>">
        <?= Icon::create("download", "clickable")->asImg("30px", ['class' => "text-bottom"]) ?>
        <?= htmlReady(_("Stud.IP")." ".$latest_stable['name']." "._("jetzt herunterladen."))?>
    </a>
</div>

<table class="default">
    <caption>
        <?= _("Weitere Downloads") ?>
    </caption>
    <thead>
        <tr>
            <th><?= _("Version") ?></th>
            <th><?= _("Datum") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($releases as $release) : ?>
        <tr>
            <td>
                <a href="<?= PluginEngine::getLink($plugin, array(), "download/".$release['name']) ?>">
                    <?= htmlReady($release['name']) ?>
                </a>
            </td>
            <td>
                <?= date("j.n.Y", strtotime($release['date'])) ?>
            </td>
            <td style="text-align: right;">
                <a href="<?= PluginEngine::getLink($plugin, array(), "download/".$release['name']) ?>">
                    <?= Icon::create("download", "clickable")->asImg("20px", ['class' => "text-bottom"]) ?>
                </a>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>