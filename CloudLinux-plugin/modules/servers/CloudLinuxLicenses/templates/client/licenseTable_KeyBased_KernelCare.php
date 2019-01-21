<?php
    echo $renderer->renderStyle('clientarea_styles');
    echo $renderer->renderScript('clientarea_scripts');
?>

<div>
    <h2 class="set_main_header"><?php echo $lang['main_header'] ?></h2>
    <div id="cl_alerts">
    <?php if (($result = $renderer->getResult($id))): ?>
        <div class="box-<?php echo $result['error'] ? 'error' : 'success' ?>">
            <?php echo $result['message'] ?>
        </div>
    <?php endif; ?>
    </div>

    <div>
        <form method="post" action="<?php echo $renderer->getUrl() ?>">
            <input type="hidden" name="method" value="newLicenseKey">
            <input type="hidden" name="sid" value="<?php echo $id; ?>">
            <?php echo $csrfField ?>
            <table width="90%" class="table table-striped">
                <tr>
                    <td width="20%"><?php echo $lang['license_key'] ?></td>
                    <td>
                        <span class="cl-text"><?php echo $keyModel->key ?></span>
                        <a href="#" class="btn btn-mini btn-primary cl-btn-change"><?php echo $lang['change'] ?></a>
                        <div class="cl-new" style="display: none;">
                            <input type="text" class="cl-new-value" name="value" value="<?php echo $keyModel->key ?>"  />
                            <input class="btn btn-primary" type="submit" style="margin-bottom: 8px;" value="<?php echo $lang['save_change'] ?>" />
                            <?php echo $lang['or_a'] ?> <a href="#" class="cl-btn-cancel"><?php echo $lang['cancel'] ?></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><?php echo $lang['limit'] ?></td>
                    <td><?php echo count($serverList) .' / '. $keyModel->limit?></td>
                </tr>
                <tr>
                    <td><?php echo $lang['license_status'] ?></td>
                <?php if ($keyModel->status === 'enabled'): ?>
                    <td style="color: #779500;"><?php echo $lang['enabled'] ?></td>
                <?php else:?>
                    <td style="color: #cc0000;"><?php echo $lang['disabled'] ?></td>
                <?php endif;?>
                </tr>
                <tr>
                    <td><?php echo $lang['license_added'] ?></td>
                    <td><?php echo $keyModel->createdDate ?></td>
                </tr>
            </table>
        </form>
    </div>

<?php if ($serverList): ?>
    <table  width="100%" border="0" cellspacing="1" cellpadding="3" class="table table-striped">
        <thead>
        <tr>
            <th colspan="3" style="text-align: center">
                <h2 class="set_main_header"><?php echo $lang['server_header'] ?>
                </h2>
            </th>
        </tr>
        <tr>
            <th>Server ID</th>
            <th>IP</th>
            <th>Created</th>
        </tr>
        </thead>

        <?php foreach($serverList as $row): ?>
            <tbody>
            <tr class="left">
                <td><?php echo $row->server_id ?></td>
                <td><?php echo $row->ip ?></td>
                <td><?php echo $row->createdDate ?></td>
            </tr>
            </tbody>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>
