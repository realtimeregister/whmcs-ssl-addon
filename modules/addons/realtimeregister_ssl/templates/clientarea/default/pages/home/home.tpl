{**********************************************************************
*
*
* This software is furnished under a license and may be used and copied
* only  in  accordance  with  the  terms  of such  license and with the
* inclusion of the above copyright notice.  This software  or any other
* copies thereof may not be provided or otherwise made available to any
* other person.  No title to and  ownership of the  software is  hereby
* transferred.
*
*
**********************************************************************}

<div class="box light">
    <div class="row">
        <div class="col-lg-12" id="addon-categories-content" >
            <table class="table dataTable no-footer" id="addon-data-list" >
                <thead>
                    <tr>
                        <th>{$ADDONLANG->T('Name')}</th>
                        <th>{$ADDONLANG->T('Username')}</th>
                        <th>{$ADDONLANG->T('Password')}</th>
                        <th>{$ADDONLANG->T('Shared By')}</th>
                        <th>{$ADDONLANG->T('Category')}</th>
                        <th style="width: 150px; text-align: center;">{$ADDONLANG->T('Actions')}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div class="well well-sm" style="margin-top:10px;">
                <button class="btn btn-success btn-inverse icon-left" type="button"  data-toggle="modal" data-target="#addon-modal-add-new">
                    <i class="glyphicon glyphicon-plus"></i>
                    {$ADDONLANG->T('Add New')}
                </button>
            </div>
            {*Modal addon-modal-new-entity*}
            <form data-toggle="validator" role="form" id="addon-form-add-new">
                <div class="modal fade bs-example-modal-lg" id="addon-modal-add-new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <h4 class="modal-title">{$ADDONLANG->T('Add New Own Password')} <strong></strong></h4>
                            </div>
                            <div class="modal-loader" style="display:none;"></div>

                            <div class="modal-body">
                                <input type="hidden" name="id" data-target="id" value="">
                                <div class="modal-alerts">
                                    <div style="display:none;" data-prototype="error">
                                        <div class="note note-danger">
                                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                            <strong></strong>
                                            <a style="display:none;" class="errorID" href=""></a>
                                        </div>
                                    </div>
                                    <div style="display:none;" data-prototype="success">
                                        <div class="note note-success">
                                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                            <strong></strong>
                                        </div>
                                    </div>
                                </div>
                                {$formAdd}
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" data-modal-action="save" id="pm-modal-addip-button-add">{$ADDONLANG->T('modal','Add')}</button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            {*Modal addon-modal-edit*}
            <form data-toggle="validator" role="form" id="addon-form-entity-edit">
                <div class="modal fade bs-example-modal-lg" id="addon-modal-edit-entity" data-modal-load="detail" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                <h4 class="modal-title">{$ADDONLANG->T('Edit Own Password')} <strong data-modal-var="username"></strong></h4>
                            </div>
                            <div class="modal-loader" style="display:none;"></div>

                            <div class="modal-body">
                                <input type="hidden" name="id" data-target="id" value="">
                                <div class="modal-alerts">
                                    <div style="display:none;" data-prototype="error">
                                        <div class="note note-danger">
                                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                            <strong></strong>
                                            <a style="display:none;" class="errorID" href=""></a>
                                        </div>
                                    </div>
                                    <div style="display:none;" data-prototype="success">
                                        <div class="note note-success">
                                            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                            <strong></strong>
                                        </div>
                                    </div>
                                </div>
                                {$formEdit}
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" data-modal-action="save" id="pm-modal-addip-button-add">{$ADDONLANG->T('Save')}</button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            {*Modal addon-modal-delete-account*}
            <div class="modal fade bs-example-modal-lg" id="addon-modal-delete-entity" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">{$ADDONLANG->T('modal','Delete Own Password')} <strong data-modal-title=""></strong></h4>
                        </div>
                        <div class="modal-loader" style="display:none;"></div>

                        <div class="modal-body">
                            <input type="hidden" name="id" data-target="id" value="">
                            <div class="modal-alerts">
                                <div style="display:none;" data-prototype="error">
                                    <div class="note note-danger">
                                        <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                        <strong></strong>
                                        <a style="display:none;" class="errorID" href=""></a>
                                    </div>
                                </div>
                                <div style="display:none;" data-prototype="success">
                                    <div class="note note-success">
                                        <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                        <strong></strong>
                                    </div>
                                </div>
                            </div>
                            <div style="margin: 30px; text-align: center;">

                                <div>{$ADDONLANG->T('Are you sure you want to delete this entry from own passwords list?')} </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-modal-action="delete" id="pm-modal-addip-button-add">{$ADDONLANG->T('modal','delete')}</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
                        </div>
                    </div>
                </div>
            </div>
            {*addon-modal-details*}
            <div class="modal fade bs-example-modal-lg" id="addon-modal-details"  data-modal-load="note" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">{$ADDONLANG->T('Details')} <strong data-modal-var="name"></strong></h4>
                        </div>
                        <div class="modal-loader" style="display:none;"></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" data-target="id" value="">
                            <div class="modal-alerts">
                                <div style="display:none;" data-prototype="error">
                                    <div class="note note-danger">
                                        <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                        <strong></strong>
                                        <a style="display:none;" class="errorID" href=""></a>
                                    </div>
                                </div>
                                <div style="display:none;" data-prototype="success">
                                    <div class="note note-success">
                                        <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
                                        <strong></strong>
                                    </div>
                                </div>
                            </div>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>{$ADDONLANG->T('Name')}</td>
                                        <td data-modal-var="name"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Username')}</td>
                                        <td data-modal-var="username"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Password')}</td>
                                        <td data-modal-var="password"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Website URL')}</td>
                                        <td data-modal-var="websiteUrl"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Login URL')}</td>
                                        <td data-modal-var="loginUrl"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Shared By')}</td>
                                        <td data-modal-var="shared"></td>
                                    </tr>
                                    <tr>
                                        <td>{$ADDONLANG->T('Note')}</td>
                                        <td data-modal-var="note"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{$ADDONLANG->T('close')}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{literal}
    <style>
        .addon-permission-disabled{
            display: inline-block; margin-top:3px; width:65px; padding:4px; text-transform: none;opacity:0.25;
        }
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            var addonDataTable;

            jQuery(document).ready(function () {
               addonDataTable = $('#addon-data-list').dataTable({
                    processing: false,
                    searching: true,
                    autoWidth: false,
                    "serverSide": false,
                    "order": [[0, "desc"]],
                    ajax: function (data, callback, settings) {
                        var filter = {
                            //    serverID: $('#pm-filters-server').val(),
                        };
                        JSONParser.request(
                                'list'
                                , {
                                    filter: filter
                                    , limit: data.length
                                    , offset: data.start
                                    , order: data.order
                                    , search: data.search
                                }
                        , function (data) {
                            callback(data);
                        }
                        );
                    },
                    'columns': [
                        , null
                                , null
                                , null
                                , null
                                , {orderable: false}

                    ],
                    'aoColumnDefs': [{
                            'bSortable': false,
                            'aTargets': ['nosort']
                        }],
                    language: {
                        "zeroRecords": "{/literal}{$ADDONLANG->absoluteT('Nothing to display')}{literal}",
                        "infoEmpty": "",
                        "search": "{/literal}{$ADDONLANG->absoluteT('Search')}{literal}",
                        "paginate": {
                            "previous": "{/literal}{$ADDONLANG->absoluteT('Previous')}{literal}"
                            , "next": "{/literal}{$ADDONLANG->absoluteT('Next')}{literal}"
                        }
                    }
                });

                $('#addon-categories-content').AddonModalActions();
                $('#addon-modal-delete-entity, #addon-form-add-new').on('hidden.bs.modal', function () {
                    var api = addonDataTable.api();
                    api.ajax.reload(function () {
                    }, false);
                });
            });

            //show password
            $("#addon-categories-content").on("click",".addon-show-password",function(e){
                e.preventDefault();
                var inputPassword = $(this).closest("div").find(".form-control");
                JSONParser.request(
                'getPassword'
                , { id: $(this).attr('data-target') }
                , function (data) {
                    inputPassword.val(data);
                });
            });
        });
    </script>

{/literal}
