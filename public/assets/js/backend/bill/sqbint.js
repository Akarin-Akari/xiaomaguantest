define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bill/sqbint/index' + location.search,
                    add_url: 'bill/sqbint/add',
                    edit_url: 'bill/sqbint/edit',
                    del_url: 'bill/sqbint/del',
                    multi_url: 'bill/sqbint/multi',
                    import_url: 'bill/sqbint/import',
                    table: 'sqb_int',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id')},
                        {field: 'client_sn', title: __('Client_sn'), operate: 'LIKE'},
                        {field: 'subject', title: __('Subject'), operate: 'LIKE'},
                        {field: 'total_amount', title: __('Total_amount'), operate:'BETWEEN'},
                        {field: 'machine_code', title: __('Machine_code'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'student.phone', title: __('Student.phone')},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
