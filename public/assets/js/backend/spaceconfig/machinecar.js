
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spaceconfig/machinecar/index' + location.search,
                    add_url: 'spaceconfig/machinecar/add',
                    edit_url: 'spaceconfig/machinecar/edit',
                    del_url: 'spaceconfig/machinecar/del',
                    multi_url: 'spaceconfig/machinecar/multi',
                    import_url: 'spaceconfig/machinecar/import',
                    table: 'machine_car',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'machine_code', title: __('Machine_code'), operate: 'LIKE'},
                        // {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')}, formatter: Table.api.formatter.label},
                        {field: 'fchrMachineDeviceID', title: __('FchrMachineDeviceID'), operate: 'LIKE'},
                        {field: 'main_versions', title: __('Main_versions'), operate: 'LIKE'},
                        {field: 'local_versions', title: __('Local_versions'), operate: 'LIKE'},
                        {field: 'LocalserialNum', title: __('LocalserialNum'), operate: 'LIKE'},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE'},
                        // {field: 'sim', title: __('Sim'), operate: 'LIKE'},
                        // {field: 'imei', title: __('Imei'), operate: 'LIKE'},
                        // {field: 'sn', title: __('Sn'), operate: 'LIKE'},
                        // {field: 'terminal_equipment', title: __('Terminal_equipment'), operate: 'LIKE'},
                        // {field: 'study_machine', title: __('Study_machine'), operate: 'LIKE'},
                        {field: 'address', title: __('Address'), operate: false,},
                        {field: 'student_code', title: __('Student_code'), operate: false,events: Table.api.events.image, formatter: Table.api.formatter.images},
                        // {field: 'experience_code', title: __('Experience_code'), operate: 'LIKE',events: Table.api.events.image, formatter: Table.api.formatter.images},
                        // {field: 'pay_code', title: __('Pay_code'), operate: 'LIKE',events: Table.api.events.image, formatter: Table.api.formatter.images},
                        // {field: 'back_window_code', title: __('Back_window_code'), operate: 'LIKE',events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'state', title: __('State'), searchList: {"0":__('State 0'),"1":__('State 1')}, formatter: Table.api.formatter.normal},
                        {field: 'collor', title: __('Collor'), searchList: {"1":__('Collor 1'),"2":__('Collor 2'),"3":__('Collor 3'),"4":__('Collor 4')}, formatter: Table.api.formatter.normal},
                        {field: 'insurance_start_time', title: __('Insurance_Start_Time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'insurance_end_time', title: __('Insurance_End_Time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},

                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});