<?php if ($server): ?>
<div class="cl-col">
    <div class="cl-license-info">
        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['ip_address'] ?></div>
            <div><?php echo $ip ?></div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['server_info'] ?></div>
            <div>
                <?php echo $server->hostname . (isset($server->server_info) ? ' ' . $server->server_info : '') ?>
            </div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['license_type'] ?></div>
            <div><?php echo $clnLicense->getName() ?></div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['license_status'] ?></div>
            <div class="cl-status-<?php echo $status ?>"><?php echo $lang[$status] ?></div>
        </div>

        <?php if ($server->createdDate): ?>
        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['create_date'] ?></div>
            <div><?php echo $server->createdDate ?></div>
        </div>
        <?php endif; ?>

        <?php if ($server->checkinDate): ?>
        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['checkin_date'] ?></div>
            <div><?php echo $server->checkinDate ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>