define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/evaluation/index' + location.search,
                    add_url: 'studentconfig/evaluation/add',
                    edit_url: 'studentconfig/evaluation/edit',
                    del_url: 'studentconfig/evaluation/del',
                    multi_url: 'studentconfig/evaluation/multi',
                    import_url: 'studentconfig/evaluation/import',
                    table: 'evaluation',
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
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'student.phone', title: __('Student.phone')},
                        {field: 'coach.name', title: __('Coach.name'), operate: 'LIKE'},
                        {field: 'space_evaluate', title: __('Space_evaluate'), operate: 'LIKE'},
                        {field: 'overall', title: __('Overall'), searchList: {"1":__('Overall 1'),"2":__('Overall 2'),"3":__('Overall 3'),"4":__('Overall 4'),"5":__('Overall 5')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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