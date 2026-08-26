<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>

<div class="panel panel-default addon-wrapper" style="text-align: left">
    <div class="panel-heading">
        <h2>{$productName}</h2>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <colgroup>
                <col style="width: 25%"/>
                <col style="width: 75%"/>
            </colgroup>
            <tbody>
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('configurationStatus')}</strong></td>
                    <td class="text-left">{$ADDONLANG->T($configurationStatus)}</td>
                </tr>
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('validTill')}</strong></td>
                    <td class="text-left">{$validTill}</td>
                </tr>
                {if $nextInvoiceDate}
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('nextInvoiceDate')}</strong></td>
                    <td class="text-left">{$nextInvoiceDate}</td>
                </tr>
                {/if}
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('directoryUrl')}</strong></td>
                    <td class="text-left">{$directoryUrl}</td>
                </tr>
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('accountKey')}</strong></td>
                    <td class="text-left">
                        <span id="account-key">********</span>
                        <button type="button" class="btn btn-xs btn-default show-credential-btn" data-type="accountKey" style="margin-left: 6px;">Show</button>
                    </td>
                </tr>
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('hmacKey')}</strong></td>
                    <td class="text-left">
                        <span id="hmac-key">********</span>
                        <button type="button" class="btn btn-xs btn-default show-credential-btn" data-type="hmacKey" style="margin-left: 6px;">Show</button>
                    </td>
                </tr>
                <tr>
                    <td class="text-left" style="vertical-align: top;"><strong>{$ADDONLANG->T('acmeDomainsLabel')}</strong></td>
                    <td class="text-left">
                        <table class="table table-condensed" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>{$ADDONLANG->T('domain')}</th>
                                    <th>{$ADDONLANG->T('addedOn')}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $domains as $domain}
                                <tr>
                                    <td>{$domain->domainName}</td>
                                    <td>{$domain->addedOn}</td>
                                    <td style="width: 1%; white-space: nowrap;">
                                        <button type="button" class="btn btn-xs btn-danger remove-domain-btn" data-domain="{$domain->domainName}">
                                            {$ADDONLANG->T('remove')}
                                        </button>
                                    </td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </td>
                </tr>
            <tr>
                <td class="text-left"><strong>{$ADDONLANG->T('usedDomains')}</strong></td>
                <td class="text-left">
                    <span class="label label-default mr-1">
                        {$ADDONLANG->T('domains')}: {$domainCount} / {$domainLimits}
                    </span>
                    <span class="label label-info mr-1">
                        {$ADDONLANG->T('wildcardDomains')}: {$wildcardDomainCount} / {$wildcardLimits}
                    </span>
                    <span class="label label-success">
                        {$ADDONLANG->T('totalDomains')}: {$domainCount + $wildcardDomainCount} / {$wildcardLimits + $domainLimits}
                    </span>
                </td>
            </tr>
            </tbody>
        </table>

        {if $domainCount > $domainLimits}
        <div class="alert alert-warning" style="margin-top: 10px;">
            {$ADDONLANG->T('domainLimitExceededWarning1')} {$domainCount - $domainLimits} {$ADDONLANG->T('domainLimitExceededWarning2')}
        </div>
        {/if}
        {if $wildcardDomainCount > $wildcardLimits}
        <div class="alert alert-warning" style="margin-top: 10px;">
            {$ADDONLANG->T('wildcardLimitExceededWarning1')} {$wildcardDomainCount - $wildcardLimits} {$ADDONLANG->T('wildcardLimitExceededWarning2')}
        </div>
        {/if}

        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-12">
                <a href="/upgrade.php?type=configoptions&id={$serviceid}" class="btn btn-primary">
                    {$ADDONLANG->T('buyDomainSpace')}
                </a>
                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addDomainsModal">
                    {$ADDONLANG->T('addDomains')}
                </button>
                <button type="button" class="btn btn-default" id="showCertbotCommandBtn">
                    {$ADDONLANG->T('showCertbotCommand')}
                </button>
                {if $showRenewButton}
                    <button type="button" id="btnRenew" class="btn btn-default">{$ADDONLANG->T('renew')}</button>
                {/if}
            </div>
        </div>
    </div>
</div>

<!-- Certbot Command Modal -->
<div class="modal fade" id="certbotCommandModal" tabindex="-1" role="dialog" aria-labelledby="certbotCommandModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="certbotCommandModalLabel">{$ADDONLANG->T('showCertbotCommand')}</h4>
            </div>
            <div class="modal-body">
                <pre id="certbotCommandOutput" style="text-align: left;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="copyCertbotCommandBtn">{$ADDONLANG->T('copyToClipboard')}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('cancel')}</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Domains Modal -->
<div class="modal fade" id="addDomainsModal" tabindex="-1" role="dialog" aria-labelledby="addDomainsModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addDomainsModalLabel">{$ADDONLANG->T('addDomains')}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger hidden" id="addDomainsError"><strong>Error!</strong> <span></span></div>
                <textarea id="addDomainsInput" rows="6" class="form-control" placeholder="{$ADDONLANG->T('acmeDomainsPlaceholder')}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('cancel')}</button>
                <button type="button" class="btn btn-primary" id="addDomainsSubmit">{$ADDONLANG->T('addDomains')}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRenew" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">{$ADDONLANG->T('Close')}</span>
                </button>
                <h4 class="modal-title">{$ADDONLANG->T('renewModalTitle')}</h4>
            </div>
            <div class="modal-body panel-body" id="modalRenewBody">

                <div class="alert alert-success hidden" id="modalRenewSuccess">
                    <strong>Success!</strong> <span></span>
                </div>
                <div class="alert alert-danger hidden" id="modalRenewDanger">
                    <strong>Error!</strong> <span></span>
                </div>
                <form class="form-horizontal" role="form" id="modalRenewForm">
                    <div class="col-sm-12" style="padding: 25px;">
                        {$ADDONLANG->T('renewModalConfirmInformation')}
                    </div>
                </form>
            </div>
            <div class="modal-footer panel-footer">
                <button type="button" id="modalRenewSubmit" class="btn btn-primary">
                    {$ADDONLANG->T('Submit')}
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {$ADDONLANG->T('Close')}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $('#Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reissue_Certificate').hide();
        const serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1';

        function runAction(action, data, onSuccess, $modalErrorTarget) {
            data['addon-action'] = action;
            $.post(serviceUrl, data, function (ret) {
                ret = ret.replace("<JSONRESPONSE#", "").replace("#ENDJSONRESPONSE>", "");
                const payload = JSON.parse(ret);
                if (payload.result === 'error') {
                    const errorMessage = payload.error || 'Error';
                    if ($modalErrorTarget && $modalErrorTarget.length) {
                        $modalErrorTarget.find('span').text(errorMessage);
                        $modalErrorTarget.removeClass('hidden');
                    } else {
                        $('#AddonAlerts').alerts('error', errorMessage);
                    }
                    return;
                }
                const msg = payload.data?.message ?? '{$ADDONLANG->T('acmeConfigurationDone')}';
                if (onSuccess) {
                    onSuccess(payload);
                    return;
                }

                $('#AddonAlerts').alerts('success', msg);
                setTimeout(function () {
                    location.reload();
                }, 1200);
            });
        }

        $('#copyCertbotCommandBtn').on('click', function () {
            const text = $('#certbotCommandOutput').text();
            navigator.clipboard.writeText(text).then(function () {
                const $btn = $('#copyCertbotCommandBtn');
                const original = $btn.text();
                $btn.text('Copied!');
                setTimeout(function () { $btn.text(original); }, 1500);
            });
        });

        $('#showCertbotCommandBtn').on('click', function () {
            $('#certbotCommandModal').modal('show');
            runAction('showCredentials', {}, (payload) => {
                const directoryUrl = `{$directoryUrl}`
                {literal}
                    $('#certbotCommandOutput').text(
`certbot register \\
--server ${directoryUrl} \\
--eab-kid ${payload.data.accountKey} \\
--eab-hmac-key ${payload.data.hmacKey}`);
                {/literal}
            })
        });

        $('.show-credential-btn').on('click', function () {
            const type = $(this).data('type');
            const targetId = type === 'accountKey' ? '#account-key' : '#hmac-key';
            const $btn = $(this);

            runAction('showCredentials', { type }, function (payload) {
                $(targetId).text(payload.data[type]);
                $btn.hide();
            })
        });

        $('.remove-domain-btn').on('click', function () {
            if (!confirm('{$ADDONLANG->T('confirmRemoveDomain')}')) return;

            const domain = $(this).data('domain');
            runAction('removeDomain', { domain });
        });

        $('#addDomainsSubmit').on('click', function () {
            const domains = $('#addDomainsInput').val()
                .split('\n')
                .map(domain => domain.trim())
                .filter(Boolean);

            $('#addDomainsError').addClass('hidden');
            runAction('addDomains', { domains }, null, $('#addDomainsError'));
        });

        $('#btnRenew').on('click', function () {
            $('#modalRenew').modal('show');
        });

        $('#modalRenewSubmit').on('click', function () {
            $('#modalRenewDanger').addClass('hidden');
            runAction('renew', {} , payload => {
                const msg = payload.data?.message ?? '{$ADDONLANG->T('acmeConfigurationDone')}';
                $('#AddonAlerts').alerts('success', msg);
                setTimeout(function(){ location.replace('viewinvoice.php?id=' + payload.data.invoiceID) }, 2000);
            }, $('#modalRenewDanger'));
        });
    });
</script>
