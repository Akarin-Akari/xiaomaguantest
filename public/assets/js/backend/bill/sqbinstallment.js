define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bill/sqbinstallment/index' + location.search,
                    add_url: 'bill/sqbinstallment/add',
                    edit_url: 'bill/sqbinstallment/edit',
                    del_url: 'bill/sqbinstallment/del',
                    multi_url: 'bill/sqbinstallment/multi',
                    import_url: 'bill/sqbinstallment/import',
                    table: 'sqb_installment',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'stu_id', title: __('Stu_id')},
                        {field: 'sn', title: __('Sn'), operate: 'LIKE'},
                        {field: 'client_sn', title: __('Client_sn'), operate: 'LIKE'},
                        {field: 'total_amount', title: __('Total_amount'), operate:'BETWEEN'},
                        {field: 'cooperation_id', title: __('Cooperation_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'student.phone', title: __('Student.phone')},
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
