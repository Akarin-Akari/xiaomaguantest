define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/reportcard/index' + location.search,
                    add_url: 'studentconfig/reportcard/add',
                    edit_url: 'studentconfig/reportcard/edit',
                    del_url: 'studentconfig/reportcard/del',
                    multi_url: 'studentconfig/reportcard/multi',
                    import_url: 'studentconfig/reportcard/import',
                    table: 'report_card',
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
                        {field: 'ordernumber', title: __('Ordernumber')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'kaochang', title: __('Kaochang'), operate: 'LIKE'},
                        {field: 'student.stu_id', title: __('Student.stu_id')},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'coach.name', title: __('Coach.name'), operate: 'LIKE'},
                        {field: 'machinecar.machine_code', title: __('Machinecar.machine_code'), operate: 'LIKE'},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject1":__('Subject_type subject1'),"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3'),"subject4":__('Subject_type subject4')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'score', title: __('Score')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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