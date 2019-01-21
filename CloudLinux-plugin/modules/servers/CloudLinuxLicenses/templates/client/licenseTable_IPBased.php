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
            <input type="hidden" name="method" value="newIPAddress">
            <input type="hidden" name="sid" value="<?php echo $id; ?>">
            <?php echo $csrfField ?>
            <table width="90%" class="table table-striped">
                <tr>
                    <td width="20%"><?php echo $lang['ip_address'] ?></td>
                    <td>
                        <span class="cl-text"><?php echo $license['ip'] ?></span>
                        <a href="#" class="btn btn-mini btn-primary cl-btn-change"><?php echo $lang['change'] ?></a>
                        <div class="cl-new" style="display: none;">
                            <input type="text" class="cl-new-value" name="value" value="<?php echo $license['ip'] ?>"  />
                            <input class="btn btn-primary" type="submit" style="margin-bottom: 8px;" value="<?php echo $lang['save_change'] ?>" />
                            <?php echo $lang['or_a'] ?> <a href="#" class="cl-btn-cancel"><?php echo $lang['cancel'] ?></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><?php echo $lang['server_info'] ?></td>
                    <td><?php echo $server->hostname . (isset($server->server_info) ? ' ' . $server->server_info : '') ?></td>
                </tr>
                <tr>
                    <td><?php echo $lang['license_type'] ?></td>
                    <td><?php echo $clnLicense->getName() ?></td>
                </tr>
                <tr>
                    <td><?php echo $lang['license_status'] ?></td>
                <?php if ($license['registered']): ?>
                    <td style="color: #779500;"><?php echo $lang['registered'] ?></td>
                <?php else:?>
                    <td style="color: #cc0000;"><?php echo $lang['unregistered'] ?></td>
                <?php endif;?>
                </tr>
                <?php if ($server->createdDate): ?>
                <tr>
                    <td><?php echo $lang['create_date'] ?></td>
                    <td><?php echo $server->createdDate ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($server->checkinDate): ?>
                    <tr>
                        <td><?php echo $lang['checkin_date'] ?></td>
                        <td><?php echo $server->checkinDate ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </form>
    </div>
</div>
