define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/reportcardai/index' + location.search,
                    add_url: 'studentconfig/reportcardai/add',
                    edit_url: 'studentconfig/reportcardai/edit',
                    del_url: 'studentconfig/reportcardai/del',
                    multi_url: 'studentconfig/reportcardai/multi',
                    import_url: 'studentconfig/reportcardai/import',
                    table: 'report_card_ai',
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
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject1":__('Subject_type subject1'),"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3'),"subject4":__('Subject_type subject4')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'score', title: __('Score')},
                        {field: 'kaochang', title: __('Kaochang'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'pass_time', title: __('Pass_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'coach.name', title: __('Coach.name'), operate: 'LIKE'},
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