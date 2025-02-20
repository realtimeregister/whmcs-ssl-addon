<script type="text/javascript">
    $(document).ready(function () {
        for (const [key,value] of Object.entries(JSON.parse('{$fillVars}'))) {
            $('input[name="' + key + '"]').val(value);
            $('textarea[name="' + key + '"]').val(value);
            $('select[name="' + key + '"]').val(value);
        }
    });
</script>

