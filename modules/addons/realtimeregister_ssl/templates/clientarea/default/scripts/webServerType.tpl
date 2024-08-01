<script type="text/javascript">
    $(document).ready(function () {
        var optionsHtml = '';

        optionsHtml = '<option value="-1" selected>Any Other</option>';

        $('#inputServerType').html(optionsHtml);
        $('#inputServerType').hide();
        $('#inputServerType').prev('label').hide();
        $('#servertype').html(optionsHtml);
        $('#servertype').hide();
        $('#servertype').prev('label').hide();
    });
</script>
