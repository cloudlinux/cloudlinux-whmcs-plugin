<?php
    $sucessUrl = $url . '&success=true';

    if ($package->isKeyBased()) {
        $title = 'Change License Key';
        $action = 'changeLicenseKey';
    } else {
        $title = 'Change IP Address';
        $action = 'changeIPAddress';
    }
?>

<script>
    $(document).ready(function() {
        $('body').on('click', '#clChangeValue', function() {
            $('#clChangeValueDialog').dialog({
                width: 400,
                'title': '<?php echo $title ?>',
                'buttons': [
                    {
                        text: 'Cancel',
                        click: function() {
                            $(this).dialog('close');
                        }
                    },
                    {
                        text: 'Save',
                        click: function() {
                            var newValue =$('#clNewValueInput').val();
                            $.ajax({
                                url: '<?php echo $url ?>',
                                type: 'POST',
                                beforeSend: function () {
                                    $('.ajax-info').remove();
                                    $('#modcmdbtns').attr('style', 'opacity: 0.2;');
                                    $('#modcmdworking')
                                        .attr('style', 'text-align: center; position: absolute;  padding-left:150px; padding-top: 15px;')
                                        .show();
                                    $('#clCommands').attr('style', 'opacity: 0.2;');
                                },
                                data: {
                                    'action': '<?php echo $action ?>',
                                    'ajax': true,
                                    'value': newValue,
                                    '<?php echo $csrf->field; ?>': '<?php echo $csrf->value; ?>'
                                }
                            }).done(function (data) {
                                if (data === 'success') {
                                    location.href = '<?php echo $sucessUrl ?>';
                                } else {
                                    $('.infobox').hide();
                                    $('.errorbox .ajax-info').remove();
                                    $('#clCommands').after('<div class="errorbox ajax-info"><strong><span class="title">Error</spam></strong><br />'+data+'</div>');
                                }
                            }).then(function () {
                                $('#modcmdworking').hide();
                                $('#modcmdbtns').attr('style', '');
                                $('#clCommands').attr('style', '');
                            });
                            $(this).dialog('close');
                        }
                    }
                ]
            }).dialog('open');
        });
    });
</script>