<script>
    var csrfToken = '<?php echo $csrfToken; ?>';
</script>

<div id="cl-app"></div>

<?php
echo $this->renderStyles([
    'addon/styles',
]);

echo $this->renderScripts([
    'addon/vendor',
    'addon/bundle',
]);
?>
