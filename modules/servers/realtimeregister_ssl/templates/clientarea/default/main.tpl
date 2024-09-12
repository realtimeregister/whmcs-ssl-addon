<div class="addon-module">
    <div id="addon-wrapper" class="module-container">
        <div class="row" id="AddonAlerts">
            {if $error}
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                    <p><strong>{$error}</strong></p>
                </div>
            {/if}
            {if $success}
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                    <p><strong>{$success}</strong></p>
                </div>
            {/if}
            <div style="display:none;" data-prototype="error">
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                    <strong></strong>
                    <a style="display:none;" class="errorID" href=""></a>
                </div>
            </div>
            <div style="display:none;" data-prototype="success">
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                    <strong></strong>
                </div>
            </div>
        </div>
        <div id="addon-containerainer">
            {$content|unescape: "html" nofilter}
        </div>
        {literal}
            <script type="text/javascript">
                $(window).on('resize', function () {
                    var height = $('.module-sidebar').height();
                    $('#addon-wrapper').css('min-height', height);
                });
            </script>
        {/literal}
    </div>
</div>
