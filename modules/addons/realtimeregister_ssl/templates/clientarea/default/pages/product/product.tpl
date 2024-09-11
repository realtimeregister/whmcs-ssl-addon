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
            <table class="table table-hover" id="addon-data-list" >
                <thead>
                    <tr>
                        <th>{$ADDONLANG->T('Product/Service')}</th>
                        <th>{$ADDONLANG->T('Username')}</th>
                        <th>{$ADDONLANG->T('Password')}</th>
                        <th>{$ADDONLANG->T('Billing Cycle')}</th>
                        <th>{$ADDONLANG->T('Next Due Date')}</th>
                        <th>{$ADDONLANG->T('IP Address')}</th>
                        <th>{$ADDONLANG->T('Status')}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody> 
            </table>
        </div>
    </div>
</div>
{literal}
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
                        JSONParser.request(
                                'list'
                                , {
                                    filter: {}
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