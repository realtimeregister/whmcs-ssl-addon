<div class="modal fade" id="modalGenerateCsr" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary">
            <div class="modal-header panel-heading" style="display:block;">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="pull-left modal-title">{$ADDONLANG->T('generateCsrModalTitle')}</h4>
            </div>
            <form>
                <div class="modal-body panel-body" id="modalgenerateCsrBody">
                    <div class="alert alert-danger hidden" id="modalgenerateCsrDanger">
                        <strong>Error!</strong> <span></span>
                    </div>
                    <form class="form-horizontal" role="form" id="modalgenerateCsrForm">
                        <div class="col-md-1"></div>
                        <div class="col-md-10" style="width:80%;">
                            <div class="form-group">
                                <label class="control-label"
                                       for="countryName">{$ADDONLANG->T('countryLabel')}</label>
                                <select class="form-control generateCsrInput" id="countryName" name="C" required="">
                                    {foreach $countries as $value => $name}
                                        <option value="{$value}" {if $value == $csrData['country']}selected=''{/if}>
                                            {$name}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="stateOrProvinceName">{$ADDONLANG->T('stateLabel')}</label>
                                <input class="form-control generateCsrInput" id="stateOrProvinceName"
                                       placeholder="{$ADDONLANG->T('statePlaceholder')}"
                                       value="{$csrData['state']}" name="ST" type="text">
                            </div>
                            <div class="form-group">
                                <label class="control-label"
                                       for="localityName">{$ADDONLANG->T('localityLabel')}</label>
                                <input class="form-control generateCsrInput" id="localityName"
                                       placeholder="{$ADDONLANG->T('localityPlaceholder')}" name="L"
                                       value="{$csrData['locality']}" type="text">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="organizationName">{$ADDONLANG->T('organizationLabel')}</label>
                                <input class="form-control generateCsrInput" id="organizationName"
                                       placeholder="{$ADDONLANG->T('organizationPlaceholder')}" name="O"
                                       value="{$csrData['organization']}" type="text">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="organizationalUnitName">{$ADDONLANG->T('organizationanUnitLabel')}</label>
                                <input class="form-control generateCsrInput" id="organizationalUnitName"
                                       placeholder="{$ADDONLANG->T('organizationalUnitPlaceholder')}" name="OU"
                                       value="{$csrData['org_unit']}" type="text">
                            </div>
                            <div class="form-group">
                                <label class="control-label"
                                       for="commonName">{$ADDONLANG->T('commonNameLabel')}</label>
                                <input class="form-control generateCsrInput" autocomplete="off" id="commonName"
                                       placeholder="{if $vars.wildcard}{$ADDONLANG->T('commonNamePlaceholderWildCard')}{else}{$ADDONLANG->T('commonNamePlaceholder')}{/if}"
                                       name="CN" value="{$csrData['common_name']}" type="text">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="emailAddress">{$ADDONLANG->T('emailAddressLabel')}</label>
                                <input class="form-control generateCsrInput" id="emailAddress"
                                       placeholder="{$ADDONLANG->T('emailAddressPlaceholder')}" name="EA"
                                       value="{$csrData['email']}" type="text">
                            </div>
                        </div>
                        <div class="col-md-1"></div>
                    </form>
                </div>
                <div class="modal-footer panel-footer">
                    <button type="button" id="modalgenerateCsrSubmit" class="btn btn-primary">
                        {$ADDONLANG->T('Submit')}
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {$ADDONLANG->T('Close')}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>