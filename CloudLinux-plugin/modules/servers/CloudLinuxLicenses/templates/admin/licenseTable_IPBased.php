<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'licenseTable_scripts.php'; ?>

<table width="100%" border="0" cellspacing="1" cellpadding="3" class="datatable">
    <thead>
        <tr>
            <th>IP Address</th>
            <th>Server Info</th>
            <th>Type</th>
            <th>Status</th>
            <th>Created</th>
            <th>Last Check-in</th>
        </tr>
    </thead>

    <tr>
        <td><?php echo $license['ip'] ?></td>
        <td><?php echo $server->hostname . (isset($server->server_info) ? ' ' . $server->server_info : '') ?></td>
        <td><?php echo $clnLicense->getName() ?></td>
    <?php if ($license['registered']): ?>
        <td style="color:#779500;">Registered</td>
    <?php else:?>
        <td style="color:#cc0000;">Unregistered</td>
    <?php endif;?>
        <td><?php echo $server->createdDate ?></td>
        <td><?php echo $server->checkinDate ?></td>
    </tr>
</table>