<?php if ($key): ?>
<div class="cl-col">
    <div class="cl-license-info">
        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['license_key'] ?></div>
            <div><?php echo $key ?></div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['license_type'] ?></div>
            <div><?php echo $clnLicense->getName() ?></div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['limit'] ?></div>
            <div><?php echo count($servers) .' / '. $keyModel->limit ?></div>
        </div>

        <div class="cl-row">
            <div class="cl-description"><?php echo $lang['license_added'] ?></div>
            <div><?php echo $keyModel->createdDate ?></div>
        </div>
    </div>

    <?php if ($servers): ?>
        <table width="100%" class="cl-table">
            <thead>
                <tr>
                    <th colspan="3" class="cl-table-header">
                        <?php echo $lang['server_header'] ?>
                    </th>
                </tr>
                <tr>
                    <th><?php echo $lang['server_id'] ?></th>
                    <th><?php echo $lang['ip_address'] ?></th>
                    <th><?php echo $lang['create_date'] ?></th>
                </tr>
            </thead>

            <?php foreach($servers as $row): ?>
            <tbody>
                <tr class="left">
                    <td><?php echo $row->id ?></td>
                    <td><?php echo $row->ip ?: '-' ?></td>
                    <td><?php echo $row->createdDate ?></td>
                </tr>
            </tbody>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>