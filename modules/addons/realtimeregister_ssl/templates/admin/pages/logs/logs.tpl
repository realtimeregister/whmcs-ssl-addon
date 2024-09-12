<style>
    #logsTable table th::after {
        display: none;
    }
</style>
<div class="box light">
    <div class="row">
        <div class="col-lg-12" id="addon-home-content" >
            <legend>{$ADDONLANG->T('title')}</legend>
            <div id="logsTable">
                <table width="100%" class="table table-striped" >
                    <thead>
                        <th>{$ADDONLANG->T('table', 'id')}</th>
                        <th>{$ADDONLANG->T('table', 'client')}</th>
                        <th>{$ADDONLANG->T('table', 'service')}</th>
                        <th>{$ADDONLANG->T('table', 'type')}</th>
                        <th>{$ADDONLANG->T('table', 'msg')}</th>
                        <th>{$ADDONLANG->T('table', 'date')}</th>
                        <th>{$ADDONLANG->T('table', 'actions')}</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <div class="buttons-list">
                <button id="clearAllLogs" class="btn btn-success" type="button">
                    {$ADDONLANG->T('button', 'clear_logs')}
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade bs-example-modal-lg" id="AddonLogRemove" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">{$ADDONLANG->T('modal','removeLog')}</h4>
            </div>
            <div class="modal-body">
                <input type='hidden' name='log_id'/>
                <h4 class="text-center">{$ADDONLANG->T('modal','removeLogInfo')} <b id="AddonremoveInformation"></b></h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-inverse" id="removeLogButton" data-dismiss="modal">{$ADDONLANG->T('modal','remove')}</button>
                <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bs-example-modal-lg" id="AddonClearLogs" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">{$ADDONLANG->T('modal','clearLogs')}</h4>
            </div>
            <div class="modal-body">
                <h4 class="text-center">{$ADDONLANG->T('modal','clearLogsInfo')} <b id="AddonremoveInformation"></b></h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-inverse" id="clearLogsButton" data-dismiss="modal">{$ADDONLANG->T('modal','Clear')}</button>
                <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
            </div>
        </div>
    </div>
</div>

<script>
    {literal}

        function removeErrorStyle(modal)
        {
            $(modal).find('.form-group').removeClass('has-error');
            cleanModalMessage(modal);
        }
        function cleanModalMessage(modal) {
            $(modal).find('#successModal').attr('style', 'display:none;');
            $(modal).find('#errorModal').attr('style', 'display:none;');
        }
        function openModal(modal) {
            //clear fields
            $(modal).find('input,select').val('');
            //remove error styling
            removeErrorStyle($(modal));
            //open modal
            $(modal).modal();
        }
        function errorModal(info, modal) {
            cleanModalMessage(modal);
            $(modal).find('#errorModal').removeAttr('style');
            $(modal).find('#errorModal').find('strong').text(info);
        }

        function removeLog(button)
        {
            var modal = button.parents('.modal');
            var log_id = modal.find('input[name="log_id"]').val();
            JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&addon-page=logs', 'POST');
            JSONParser.request('removeLog', {log_id: log_id}, function (data) {
                if (data.success) {
                    $('#logsTable table').DataTable().ajax.reload();
                    $(modal).modal('hide');
                } else {
                    errorModal(data.error, modal);
                }
            });
        }

        function clearLogs(button)
        {
            var modal = button.parents('.modal');
            JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&addon-page=logs', 'POST');
            JSONParser.request('clearLogs', {}, function (data) {
                if (data.success) {
                    $('#logsTable table').DataTable().ajax.reload();
                    $(modal).modal('hide');
                } else {
                    errorModal(data.error, modal);
                }
            });
        }

        function initDatatable()
        {
            $('#logsTable table').DataTable({
                "destroy": true,
                "responsive": true,
                "lengthChange": false,
                "searching": true,
                "processing": true,
                "serverSide": true,
                "order": [[0, "asc"]],
                "bInfo": false,
                ajax: function (data, callback, settings) {
                    let filter = {};
                    JSONParser.request(
                        'getLogs',
                        {json: true,'addon-page':'logs',filter:filter,order:data.order[0],limit: data.length,offset: data.start,search:data.search.value},
                        function (data) {
                            callback(data);
                        }
                    );
                },
                "aoColumns": [
                    {'sType': 'natural', "bVisible": true, "responsivePriority": 1},
                    {'sType': 'natural', "bVisible": true, "responsivePriority": 2},
                    {'sType': 'natural', "bVisible": true, "responsivePriority": 3},
                    {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 4},
                    {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 5},
                    {'sType': 'natural', "bVisible": true, "responsivePriority": 6},
                    {'sType': 'natural', 'bVisible': true, 'bSortable': false, 'bSearchable': false, "responsivePriority": 0},
                ]
            });
        }
        $(document).ready(function () {
            initDatatable();

            $(document).on('click', '.deleteItem', function () {
                var modal = $("#AddonLogRemove");
                openModal(modal);
                $(modal).find('input[name="log_id"]').val($(this).data('id'));
            });

            $(document).on('click', '#clearAllLogs', function () {
                var modal = $("#AddonClearLogs");
                openModal(modal);
            });

            $(document).on('click', '#removeLogButton', function () {
                removeLog($(this));
            });

            $(document).on('click', '#clearLogsButton', function () {
                clearLogs($(this));
            });

        });
    {/literal}
</script>

