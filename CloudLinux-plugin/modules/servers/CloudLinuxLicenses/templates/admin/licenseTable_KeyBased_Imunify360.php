<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'licenseTable_scripts.php'; ?>

<?php if ($keyModel): ?>
    <table  width="100%" border="0" cellspacing="1" cellpadding="3" class="datatable">
        <thead>
            <tr>
                <th>Description</th>
                <th>Key</th>
                <th>License Type</th>
                <th>Limit/Servers</th>
                <th>Created</th>
            </tr>
        </thead>

        <tr>
            <td><?php echo $keyModel->note ?></td>
            <td><?php echo $keyModel->key ?></td>
            <td><?php echo $keyModel->getType($package) ?></td>
            <td><?php echo count($serverList) .' / '. $keyModel->limit?></td>
            <td><?php echo $keyModel->createdDate ?></td>
        </tr>
    </table>
<?php else: ?>
    <span style="color: red">Key "<?php echo $key ?>" not found</span>
<?php endif;?>

<?php if ($serverList): ?>
    <table  width="100%" border="0" cellspacing="1" cellpadding="3" class="datatable">
        <thead>
        <tr>
            <th>Server ID</th>
            <th>IP</th>
            <th>Created</th>
        </tr>
        </thead>

    <?php foreach($serverList as $row): ?>
        <tr>
            <td><?php echo $row->id ?></td>
            <td><?php echo $row->ip ?: '-' ?></td>
            <td><?php echo $row->createdDate ?></td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>